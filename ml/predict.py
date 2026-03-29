import sys
import json
import base64
import math
import joblib
import pandas as pd
import warnings
import os

warnings.filterwarnings("ignore")

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
os.chdir(BASE_DIR)


def _entropy(probs):
    eps = 1e-12
    return -sum(p * math.log(p + eps) for p in probs if p > 0)


def _confidence_percent(probs, classes):
    max_p = max(probs)
    n = len(probs)
    if n <= 1:
        return round(100 * max_p, 2)
    h = _entropy(probs)
    h_max = math.log(n)
    certainty = 1.0 - (h / h_max) if h_max > 0 else 1.0
    certainty = max(0.0, min(1.0, certainty))
    # Blend max class probability with distribution sharpness (more honest on synthetic models)
    blended = 0.62 * max_p + 0.38 * certainty
    return round(100 * max(0.08, min(0.99, blended)), 2)


try:
    base64_data = sys.argv[1]
    json_data = base64.b64decode(base64_data).decode("utf-8")
    student_data = json.loads(json_data)

    degree = student_data.get("Degree", "GENERIC")
    if "Degree" in student_data:
        del student_data["Degree"]

    def try_load(deg):
        mp = f"{deg}_rf_model.joblib"
        sp = f"{deg}_scaler.joblib"
        cp = f"{deg}_model_columns.joblib"
        if not (os.path.isfile(mp) and os.path.isfile(sp) and os.path.isfile(cp)):
            return None
        return joblib.load(mp), joblib.load(sp), joblib.load(cp), deg

    bundle = try_load(degree)
    used_degree = degree
    if bundle is None and degree != "GENERIC":
        bundle = try_load("GENERIC")
        used_degree = "GENERIC"
    if bundle is None:
        print(json.dumps({"error": f"No model for {degree} (and no GENERIC fallback)."}))
        sys.exit(1)

    model, scaler, model_columns, used_degree = bundle

    df = pd.DataFrame([student_data])
    df = pd.get_dummies(df, dtype=int)

    for col in model_columns:
        if col not in df.columns:
            df[col] = 0
    df = df[model_columns]

    X_scaled = scaler.transform(df)
    pred = model.predict(X_scaled)[0]
    probs = model.predict_proba(X_scaled)[0]
    classes = model.classes_

    order = sorted(range(len(probs)), key=lambda i: probs[i], reverse=True)
    top = []
    for i in order[:3]:
        top.append({"profession": str(classes[i]), "probability_percent": round(float(probs[i]) * 100, 2)})

    conf = _confidence_percent(probs.tolist(), classes)

    print(
        json.dumps(
            {
                "profession": str(pred),
                "probability_percent": conf,
                "top_matches": top,
                "model_degree": used_degree,
            }
        )
    )

except Exception as e:
    print(json.dumps({"error": f"Python Error: {str(e)}"}))
