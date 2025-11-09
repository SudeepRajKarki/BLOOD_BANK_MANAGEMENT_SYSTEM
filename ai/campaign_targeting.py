from sklearn.cluster import KMeans
import numpy as np

def _to_numeric_matrix(campaigns):
    """Convert campaign data into numeric form for clustering."""
    if not campaigns:
        return np.array([[70, 5], [65, 10], [20, 30], [90, 2], [25, 25]]), []

    if isinstance(campaigns[0], (list, tuple)):
        return np.array(campaigns, dtype=float), [None] * len(campaigns)

    rows, meta = [], []
    for c in campaigns:
        if isinstance(c, dict):
            turnout = float(c.get('turnout_rate', 0) or 0)
            shortages = float(c.get('shortage_reports', 0) or 0)
            wait = float(c.get('avg_wait_minutes', 0) or 0)
            rows.append([turnout, shortages, wait])
            meta.append(c)
    mat = np.array(rows, dtype=float)
    return mat, meta


def recommend_campaign(data=None):
    """Return readable campaign targeting recommendations."""
    try:
        campaigns = data.get('campaigns') if data else None
        mat, meta = _to_numeric_matrix(campaigns)
        n_samples = mat.shape[0]
        n_clusters = min(3, n_samples) if n_samples >= 2 else 1

        kmeans = KMeans(n_clusters=n_clusters, random_state=0)
        kmeans.fit(mat)
        labels = kmeans.labels_.tolist()
        centers = kmeans.cluster_centers_.tolist()

        result = {
            "labels": labels,
            "centers": centers,
        }

        # 🧩 If we have location data, build readable insights
        if meta and any(isinstance(m, dict) and m.get("location") for m in meta):
            cluster_scores = {}
            for idx, lbl in enumerate(labels):
                row = mat[idx]
                turnout, shortages = row[0], row[1] if len(row) > 1 else 0
                score = turnout - (shortages * 0.5)
                cluster_scores.setdefault(lbl, []).append(score)

            avg_scores = {k: float(sum(v) / len(v)) for k, v in cluster_scores.items()}
            best_cluster = max(avg_scores.items(), key=lambda x: x[1])[0]

            recommended_locations = [
                m.get("location")
                for i, m in enumerate(meta)
                if isinstance(m, dict) and labels[i] == best_cluster
            ]

            seen, dedup = set(), []
            for loc in recommended_locations:
                if loc not in seen:
                    seen.add(loc)
                    dedup.append(loc)

            result["recommended_locations"] = dedup

            # 🌍 Generate human-readable summary
            avg_turnout = round(mat[:, 0].mean(), 1)
            avg_shortages = round(mat[:, 1].mean(), 1) if mat.shape[1] > 1 else 0

            summary = (
                f"Across {n_samples} past campaigns, the average turnout rate was {avg_turnout}% "
                f"and average shortage reports were {avg_shortages}. "
            )

            if dedup:
                summary += (
                    f"Based on data patterns, the best performing cluster shows higher donor turnout "
                    f"and fewer shortages. You should prioritize upcoming campaigns in: "
                    f"{', '.join(dedup)}."
                )
            else:
                summary += "No specific locations were strongly recommended."

            result["summary"] = summary

        return result
    except Exception as e:
        return {"error": "processing_error", "message": str(e)}
