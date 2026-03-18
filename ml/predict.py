import sys
import json
import base64
import joblib
import pandas as pd
import warnings

warnings.filterwarnings("ignore")

try:
    # 1. Decode the incoming JSON from PHP
    base64_data = sys.argv[1]
    json_data = base64.b64decode(base64_data).decode('utf-8')
    student_data = json.loads(json_data)

    # 2. Extract the degree
    degree = student_data.get("Degree", "BSIT") 

    # 3. Set dynamic paths
    model_path = f"{degree}_rf_model.joblib"
    scaler_path = f"{degree}_scaler.joblib"
    cols_path = f"{degree}_model_columns.joblib"

    # 4. Attempt to load the specific model
    try:
        model = joblib.load(model_path)
        scaler = joblib.load(scaler_path)
        model_columns = joblib.load(cols_path)
    except FileNotFoundError:
        print(json.dumps({"error": f"Model for {degree} not found."}))
        sys.exit(1)

    # 5. Prepare the dataframe & convert text to numbers
    if "Degree" in student_data:
        del student_data["Degree"]
        
    df = pd.DataFrame([student_data])
    
    # THE MATCHING FIX: Convert text to numbers just like in training
    df = pd.get_dummies(df, dtype=int)

    # 6. Align columns perfectly with the training data
    for col in model_columns:
        if col not in df.columns:
            df[col] = 0
            
    df = df[model_columns]

    # 7. Scale and predict
    X_scaled = scaler.transform(df)
    
    prediction = model.predict(X_scaled)[0]
    probabilities = model.predict_proba(X_scaled)[0]
    max_prob = max(probabilities) * 100

    # 8. Output to PHP
    print(json.dumps({
        "profession": str(prediction),
        "probability_percent": round(max_prob, 2)
    }))

except Exception as e:
    print(json.dumps({"error": f"Python Error: {str(e)}"}))