import os
import argparse
import pandas as pd
import joblib
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
os.chdir(BASE_DIR)

parser = argparse.ArgumentParser()
parser.add_argument("--data", default="alumni_data.csv", help="Training CSV (already structured).")
args = parser.parse_args()

df = pd.read_csv(args.data)
degrees = df["Degree"].unique()

print("Starting training process...\n")

for degree in degrees:
    print(f"--- Training model for {degree} ---")

    program_data = df[df["Degree"] == degree].copy()
    program_data = program_data.drop("Degree", axis=1)

    X = program_data.drop("Profession", axis=1)
    y = program_data["Profession"]

    X = pd.get_dummies(X, dtype=int)
    X = X.fillna(0)

    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)

    # Simpler forest gives stronger class confidence on this synthetic/mock schema.
    model = RandomForestClassifier(
        n_estimators=100,
        random_state=42,
        n_jobs=-1,
    )
    model.fit(X_scaled, y)

    joblib.dump(model, f"{degree}_rf_model.joblib")
    joblib.dump(scaler, f"{degree}_scaler.joblib")
    joblib.dump(X.columns.tolist(), f"{degree}_model_columns.joblib")

    print(f"Success: saved {degree} ({len(y)} rows, {len(X.columns)} features)\n")

print("All models trained successfully.")
