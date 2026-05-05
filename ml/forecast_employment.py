"""
Employment rate time-series forecast: ARIMA, OLS linear regression, or RandomForest regressor.
Invoked from PHP with base64-encoded JSON on argv[1], same pattern as predict.py.

Heavy imports (statsmodels, pandas, sklearn) are lazy so linear regression avoids ~1s+ cold load.
"""
from __future__ import annotations

import base64
import json
import sys
import warnings
from typing import Any, Dict, List, Optional, Tuple

import numpy as np

warnings.filterwarnings("ignore")
# Avoid stderr noise breaking PHP shell_exec JSON (2>&1 merges stderr into stdout).
warnings.filterwarnings("ignore", category=UserWarning, module="statsmodels")

_SM_ARIMA = None  # lazy: statsmodels.tsa.arima.model.ARIMA


def _get_sm_arima():
    global _SM_ARIMA
    if _SM_ARIMA is False:
        return None
    if _SM_ARIMA is None:
        try:
            from statsmodels.tsa.arima.model import ARIMA as SM_ARIMA

            _SM_ARIMA = SM_ARIMA
        except ImportError:  # pragma: no cover
            _SM_ARIMA = False
    return _SM_ARIMA if _SM_ARIMA is not False else None


def _clip_rate(x: float) -> float:
    return float(max(0.0, min(100.0, x)))


def _linear_forecast(
    years: List[int], rates: List[float], horizon: int
) -> Tuple[np.ndarray, np.ndarray, np.ndarray, np.ndarray, np.ndarray]:
    from sklearn.linear_model import LinearRegression

    X = np.array(years, dtype=float).reshape(-1, 1)
    y = np.array(rates, dtype=float)
    model = LinearRegression().fit(X, y)
    fitted = model.predict(X)
    last_y = max(years)
    fut_years = np.array([last_y + 1 + i for i in range(horizon)], dtype=float).reshape(-1, 1)
    forecast = model.predict(fut_years)
    residuals = y - fitted
    se = float(np.std(residuals, ddof=1)) if len(residuals) > 1 else 8.0
    se = max(se, 2.0)
    lo = forecast - 1.96 * se
    hi = forecast + 1.96 * se
    lo = np.clip(lo, 0.0, 100.0)
    hi = np.clip(hi, 0.0, 100.0)
    return fitted, forecast, lo, hi, se


def _rf_forecast(
    years: List[int], rates: List[float], horizon: int
) -> Tuple[np.ndarray, np.ndarray, np.ndarray, np.ndarray]:
    from sklearn.ensemble import RandomForestRegressor

    X = np.array(years, dtype=float).reshape(-1, 1)
    y = np.array(rates, dtype=float)
    depth = min(4, max(2, len(years)))
    n_est = min(200, max(25, len(years) * 15))
    rf = RandomForestRegressor(
        n_estimators=n_est, max_depth=depth, random_state=42, min_samples_leaf=1
    )
    rf.fit(X, y)
    fitted = rf.predict(X)
    last_y = max(years)
    fut = np.array([last_y + 1 + i for i in range(horizon)], dtype=float).reshape(-1, 1)
    forecast = rf.predict(fut)
    residuals = y - fitted
    se = float(np.std(residuals, ddof=1)) if len(residuals) > 1 else 8.0
    se = max(se, 2.0)
    lo = np.clip(forecast - 1.96 * se, 0.0, 100.0)
    hi = np.clip(forecast + 1.96 * se, 0.0, 100.0)
    return fitted, forecast, lo, hi


def _arima_endog_series(rates: List[float]):
    """RangeIndex = one step per SQL row (gaps in calendar years are not misinterpreted as missing periods)."""
    import pandas as pd

    n = len(rates)
    return pd.Series(rates, index=pd.RangeIndex(0, n), dtype=float)


def _arima_forecast(
    years: List[int], rates: List[float], horizon: int
) -> Tuple[np.ndarray, np.ndarray, np.ndarray, np.ndarray, Optional[str]]:
    note = None
    endog = _arima_endog_series(rates)
    n = len(endog)
    fitted = np.array(rates, dtype=float)
    forecast = np.zeros(horizon, dtype=float)
    lower = np.zeros(horizon, dtype=float)
    upper = np.zeros(horizon, dtype=float)

    SM_ARIMA = _get_sm_arima()
    if SM_ARIMA is None:
        note = "statsmodels not installed; used linear regression"
        f1, fc, lo, hi, _ = _linear_forecast(years, rates, horizon)
        return f1, fc, lo, hi, note

    if n < 4:
        note = "fewer than 4 points; used linear regression instead of ARIMA"
        return _linear_forecast(years, rates, horizon)[:4] + (note,)

    best_res = None
    best_aic = np.inf
    # Short series: a small order set is enough; each extra fit was dominating latency.
    orders = [(1, 1, 1), (0, 1, 1), (1, 0, 0)]
    fit_kw = {"warn_convergence": False, "maxiter": 75}
    for order in orders:
        try:
            m = SM_ARIMA(endog, order=order, trend="c" if order[1] == 0 else "n")
            res = m.fit(method_kwargs=fit_kw)
            if res.aic < best_aic:
                best_aic = res.aic
                best_res = res
        except Exception:
            continue

    if best_res is None:
        note = "ARIMA did not converge; used linear regression"
        f1, fc, lo, hi, _ = _linear_forecast(years, rates, horizon)
        return f1, fc, lo, hi, note

    try:
        # Positional 0..n-1 matches sorted endog row order
        ins = best_res.get_prediction(start=0, end=n - 1)
        fitted = np.asarray(ins.predicted_mean, dtype=float).reshape(-1)
        if fitted.size != n:
            fitted = np.array(rates, dtype=float)
    except Exception:
        fitted = np.array(rates, dtype=float)

    try:
        with warnings.catch_warnings():
            warnings.simplefilter("ignore")
            fc = best_res.get_forecast(steps=horizon)
        forecast = np.asarray(fc.predicted_mean, dtype=float).reshape(-1)
        conf = fc.conf_int(alpha=0.05)
        lower = np.asarray(conf.iloc[:, 0], dtype=float).reshape(-1)
        upper = np.asarray(conf.iloc[:, 1], dtype=float).reshape(-1)
        if np.any(np.isnan(forecast)) or forecast.shape[0] != horizon:
            raise ValueError("invalid ARIMA forecast output")
    except Exception:
        note = "ARIMA forecast used linear regression (index/sparse data or degenerate fit)"
        _, fc_arr, lo, hi, _ = _linear_forecast(years, rates, horizon)
        forecast = fc_arr
        lower = lo
        upper = hi

    forecast = np.clip(forecast, 0.0, 100.0)
    lower = np.clip(lower, 0.0, 100.0)
    upper = np.clip(upper, 0.0, 100.0)
    return fitted, forecast, lower, upper, note


def run(payload: Dict[str, Any]) -> Dict[str, Any]:
    years = [int(y) for y in payload["years"]]
    rates = [float(r) for r in payload["rates"]]
    horizon = int(payload.get("horizon", 3))
    method = str(payload.get("method", "linear_regression")).lower().strip()

    if len(years) != len(rates):
        return {"ok": False, "error": "years and rates length mismatch"}
    if len(years) < 2:
        return {"ok": False, "error": "need at least two cohort years with assessment data"}
    horizon = max(1, min(10, horizon))

    pairs = sorted(zip(years, rates), key=lambda x: x[0])
    years = [p[0] for p in pairs]
    rates = [p[1] for p in pairs]

    note: Optional[str] = None
    if method == "arima":
        fitted, forecast, lower, upper, note = _arima_forecast(years, rates, horizon)
        if note and "linear regression" in note.lower():
            method = "linear_regression"
    elif method == "random_forest":
        fitted, forecast, lower, upper = _rf_forecast(years, rates, horizon)
    else:
        method = "linear_regression"
        fitted, forecast, lower, upper, _se = _linear_forecast(years, rates, horizon)

    last_year = max(years)
    fut_years = [last_year + 1 + i for i in range(horizon)]

    all_years = years + fut_years
    actual_extended = [round(_clip_rate(r), 2) for r in rates] + [None] * horizon
    fitted_extended = [round(_clip_rate(float(f)), 2) for f in fitted] + [None] * horizon
    forecast_extended = [None] * len(years) + [round(_clip_rate(float(f)), 2) for f in forecast]

    table_rows = []
    for i, y in enumerate(years):
        table_rows.append(
            {
                "year": y,
                "data_type": "Actual",
                "employment_rate": round(_clip_rate(rates[i]), 2),
                "lower": None,
                "upper": None,
            }
        )
    for i, y in enumerate(fut_years):
        table_rows.append(
            {
                "year": y,
                "data_type": "Forecast",
                "employment_rate": round(_clip_rate(float(forecast[i])), 2),
                "lower": round(_clip_rate(float(lower[i])), 2),
                "upper": round(_clip_rate(float(upper[i])), 2),
            }
        )

    return {
        "ok": True,
        "method": method,
        "years_historical": years,
        "rates_actual": [round(_clip_rate(r), 2) for r in rates],
        "chart_labels": [str(y) for y in all_years],
        "series_actual": actual_extended,
        "series_fitted": fitted_extended,
        "series_forecast": forecast_extended,
        "forecast_years": fut_years,
        "forecast_lower": [round(_clip_rate(float(x)), 2) for x in lower],
        "forecast_upper": [round(_clip_rate(float(x)), 2) for x in upper],
        "table_rows": table_rows,
        "note": note,
    }


def main() -> None:
    if len(sys.argv) < 2:
        print(json.dumps({"ok": False, "error": "missing base64 payload"}))
        sys.exit(1)
    try:
        raw = base64.b64decode(sys.argv[1]).decode("utf-8")
        payload = json.loads(raw)
        out = run(payload)
        print(json.dumps(out))
    except Exception as e:  # pragma: no cover
        print(json.dumps({"ok": False, "error": str(e)}))
        sys.exit(1)


if __name__ == "__main__":
    main()
