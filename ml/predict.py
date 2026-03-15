import sys
import json
import joblib
import pandas as pd
import base64

# 1. Load the saved "brain"
try:
    model = joblib.load('plp_rf_model.joblib')
    scaler = joblib.load('plp_scaler.joblib')
    model_columns = joblib.load('plp_model_columns.joblib')
except:
    print(json.dumps({"error": "Model files not found. Train the model first."}))
    sys.exit(1)

# 2. Decode the Base64 string sent from PHP
try:
    encoded_data = sys.argv[1]
    # Decode Base64 back into a JSON string
    student_json = base64.b64decode(encoded_data).decode('utf-8')
    student_data = json.loads(student_json)
except Exception as e:
    print(json.dumps({"error": f"Failed to decode data: {str(e)}"}))
    sys.exit(1)

# 3. Convert the data into a format the model understands
df = pd.DataFrame([student_data])
df = pd.get_dummies(df)

# Ensure the new data has the exact same columns as the training data
df = df.reindex(columns=model_columns, fill_value=0)

# 4. Scale and Predict!
scaled_data = scaler.transform(df)
probability = model.predict_proba(scaled_data)[0][1] 
is_employable = int(model.predict(scaled_data)[0])

# 5. Output the result back to PHP as JSON
result = {
    "employable": True if is_employable == 1 else False,
    "probability_percent": round(probability * 100, 1)
}
print(json.dumps(result))