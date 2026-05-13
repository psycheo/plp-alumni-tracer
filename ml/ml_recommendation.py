import sys
import json
import mysql.connector
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

def get_recommendations(alumni_profile_text):
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="", 
            database="plp_tracer"
        )
    except Exception as e:
        return {"error": f"Database connection failed: {str(e)}"}

    # Fetch Jobs & Companies Data
    query = """
        SELECT j.id as job_id, j.job_title, j.requirements_text, j.location, 
               c.name as company_name, c.industry 
        FROM ml_jobs_dataset j
        JOIN ml_companies_dataset c ON j.company_id = c.id
    """
    cursor = conn.cursor(dictionary=True)
    cursor.execute(query)
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    df = pd.DataFrame(rows)

    if df.empty:
        return {"jobs": []}

    # Combine text features for the model
    df['combined_features'] = df['job_title'] + " " + df['requirements_text'] + " " + df['industry']
    
    # TF-IDF & Cosine Similarity Setup
    vectorizer = TfidfVectorizer(stop_words='english')
    documents = df['combined_features'].tolist()
    
    # Insert the alumni's text at the top (index 0) to compare against all jobs
    documents.insert(0, alumni_profile_text) 

    # Generate vectors
    tfidf_matrix = vectorizer.fit_transform(documents)
    
    # Calculate similarity between Alumni (index 0) and all jobs (index 1 to end)
    cosine_sim = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:]).flatten()
    
    # 5. Rank and format results
    df['match_score'] = cosine_sim
    df_sorted = df.sort_values('match_score', ascending=False)
    
    # Filter out 0% matches and get top 10
    results = df_sorted[df_sorted['match_score'] > 0.05].head(10)
    
    output = []
    for _, row in results.iterrows():
        output.append({
            "title": row['job_title'],
            "company": row['company_name'],
            "location": row['location'],
            "match_percentage": round(row['match_score'] * 100, 1)
        })

    return {"ok": True, "jobs": output}

if __name__ == "__main__":
    if len(sys.argv) > 1:
        alumni_text = sys.argv[1]
        results = get_recommendations(alumni_text)
        print(json.dumps(results))
    else:
        print(json.dumps({"ok": False, "error": "No alumni profile text provided."}))