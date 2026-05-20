import sys
import json
import os

# ── DB config ──────────────────────────────────────────────────────────────
DB_HOST   = os.getenv("DB_HOST",   "localhost")
DB_USER   = os.getenv("DB_USER",   "root")
DB_PASS   = os.getenv("DB_PASS",   "")
DB_NAME   = os.getenv("DB_NAME",   "plp_tracer")
DB_PORT   = int(os.getenv("DB_PORT", 3306))
TOP_N     = 5   # how many jobs to return

# ── Entry ──────────────────────────────────────────────────────────────────
def main():
    query_text = sys.argv[1].strip() if len(sys.argv) > 1 else "professional"
    if not query_text:
        query_text = "professional"

    try:
        jobs = fetch_jobs()
    except Exception as e:
        print(json.dumps({"jobs": [], "error": f"DB error: {str(e)}"}))
        return

    if not jobs:
        print(json.dumps({"jobs": []}))
        return

    recommendations = rank_jobs(query_text, jobs)
    print(json.dumps({"jobs": recommendations}, ensure_ascii=False))


# ── Database ───────────────────────────────────────────────────────────────
def fetch_jobs():
    """Return all active jobs joined with company info."""
    import mysql.connector

    conn = mysql.connector.connect(
        host=DB_HOST, user=DB_USER, password=DB_PASS,
        database=DB_NAME, port=DB_PORT
    )
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT
            j.id,
            j.title,
            j.qualifications,
            j.skills,
            j.salary,
            j.program_id,
            c.name  AS company_name,
            c.industry
        FROM partner_jobs  j
        JOIN partner_companies c ON c.id = j.company_id
        WHERE j.is_active = 1
    """)
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows


# ── TF-IDF + Cosine similarity ─────────────────────────────────────────────
def rank_jobs(query: str, jobs: list) -> list:
    """
    Build a TF-IDF corpus from each job's combined text (title + skills +
    qualifications + industry), rank against the alumni's query, return top N.
    """
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.metrics.pairwise import cosine_similarity

    # Combine fields into a single document per job
    corpus = []
    for job in jobs:
        doc = " ".join(filter(None, [
            job.get("title", ""),
            job.get("skills", ""),
            job.get("qualifications", ""),
            job.get("industry", ""),
        ]))
        corpus.append(doc)

    # Query is the first document; corpus items follow
    all_docs = [query] + corpus

    vectorizer = TfidfVectorizer(
        stop_words="english",
        ngram_range=(1, 2),   # unigrams + bigrams for better skill matching
        min_df=1,
        sublinear_tf=True,
    )
    tfidf_matrix = vectorizer.fit_transform(all_docs)

    # Similarity of query (row 0) vs every job (rows 1..)
    query_vec  = tfidf_matrix[0]
    job_vecs   = tfidf_matrix[1:]
    scores     = cosine_similarity(query_vec, job_vecs).flatten()

    # Sort descending; take top N
    ranked_indices = scores.argsort()[::-1][:TOP_N]

    results = []
    for idx in ranked_indices:
        job  = jobs[idx]
        score = float(scores[idx])
        results.append({
            "id":           job["id"],
            "title":        job["title"],
            "company":      job["company_name"],
            "industry":     job["industry"],
            "skills":       job["skills"],
            "qualifications": job["qualifications"],
            "salary":       job["salary"],
            "program_id":   job["program_id"],
            "score":        round(score, 4),
        })

    return results


if __name__ == "__main__":
    main()