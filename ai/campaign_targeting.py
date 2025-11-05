
from sklearn.cluster import KMeans
import numpy as np


def _to_numeric_matrix(campaigns):
    """Convert a list of campaign descriptors to a numeric matrix suitable for clustering.

    Accepts either:
      - list of lists (e.g. [[turnout, shortages], ...])
      - list of dicts (e.g. [{"turnout_rate":.., "shortage_reports":.., "avg_wait_minutes":.., "location":..}, ...])
    Returns: (matrix, meta) where meta is list of original items (used for mapping back locations)
    """
    if not campaigns:
        return np.array([[70, 5], [65, 10], [20, 30], [90, 2], [25, 25]]), []

    # If list of lists/tuples
    if isinstance(campaigns[0], (list, tuple)):
        try:
            mat = np.array(campaigns, dtype=float)
            return mat, [None] * mat.shape[0]
        except Exception:
            # fallback
            pass

    # If list of dicts/objects, extract numeric features consistently
    rows = []
    meta = []
    for c in campaigns:
        if isinstance(c, dict):
            turnout = float(c.get('turnout_rate', 0) or 0)
            shortages = float(c.get('shortage_reports', 0) or 0)
            wait = float(c.get('avg_wait_minutes', 0) or 0)
            # You can add more features later (donor_count, inventory shortfall, etc.)
            rows.append([turnout, shortages, wait])
            meta.append(c)
        else:
            # try to coerce generic entries
            try:
                arr = np.array(list(c), dtype=float)
                rows.append(arr.tolist())
                meta.append(None)
            except Exception:
                # skip invalid
                continue

    if not rows:
        # fallback default
        return np.array([[70, 5], [65, 10], [20, 30], [90, 2], [25, 25]]), []

    # Pad rows to same length if necessary
    max_len = max(len(r) for r in rows)
    norm_rows = [r + [0] * (max_len - len(r)) for r in rows]
    mat = np.array(norm_rows, dtype=float)
    return mat, meta


def recommend_campaign(data=None):
    """Recommend campaign clusters/locations based on past campaign stats.

    Input examples accepted (both supported):
      - {"campaigns": [[70,5],[40,20], ...]}
      - {"campaigns": [{"location":"X","turnout_rate":70,"shortage_reports":5}, ...], "inventory": [...]}

    Returns a JSON-friendly dict with labels, centers and human-friendly recommendations when possible.
    """
    try:
        campaigns = data.get('campaigns') if data else None
        mat, meta = _to_numeric_matrix(campaigns)

        # Determine number of clusters (at most min(5, n_samples))
        n_samples = mat.shape[0]
        n_clusters = 2 if n_samples >= 2 else 1

        kmeans = KMeans(n_clusters=n_clusters, random_state=0)
        kmeans.fit(mat)

        labels = kmeans.labels_.tolist()
        centers = kmeans.cluster_centers_.tolist()

        result = {
            'labels': labels,
            'centers': centers,
        }

        # If we have meta with locations, create simple recommendations: pick cluster with highest average turnout and low shortages
        if meta and any(isinstance(m, dict) and m.get('location') for m in meta):
            # compute cluster scores (higher turnout, lower shortages preferred)
            cluster_scores = {}
            for idx, lbl in enumerate(labels):
                row = mat[idx]
                # assume features: [turnout, shortages, wait]
                turnout = float(row[0])
                shortages = float(row[1]) if row.shape[0] > 1 else 0.0
                score = turnout - (shortages * 0.5)
                cluster_scores.setdefault(lbl, []).append(score)

            avg_scores = {k: float(sum(v) / len(v)) for k, v in cluster_scores.items()}
            # choose best cluster
            best_cluster = max(avg_scores.items(), key=lambda x: x[1])[0]

            recommended_locations = [m.get('location') for i, m in enumerate(meta) if isinstance(m, dict) and labels[i] == best_cluster]
            # deduplicate preserving order
            seen = set()
            dedup = []
            for loc in recommended_locations:
                if loc not in seen:
                    seen.add(loc)
                    dedup.append(loc)

            result['recommended_locations'] = dedup

        return result
    except Exception as e:
        # Return an error structure; the FastAPI layer will convert to 500 unless handled.
        return {'error': 'processing_error', 'message': str(e)}
