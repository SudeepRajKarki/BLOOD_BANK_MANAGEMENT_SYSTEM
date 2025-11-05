
from sklearn.linear_model import LogisticRegression
import numpy as np

# Small synthetic training data
X = np.array([
    [15, 1.0, 12], [20, 0.95, 8], [30, 0.9, 5], [45, 0.8, 4], [60, 0.75, 3],
    [70, 0.6, 2], [80, 0.4, 2], [90, 0.3, 1], [100, 0.2, 1], [110, 0.15, 1],
    [120, 0.1, 0], [10, 0.98, 10], [25, 0.85, 6], [35, 0.7, 3], [65, 0.5, 2],
    [85, 0.25, 1], [95, 0.1, 0], [5, 1.0, 15], [40, 0.6, 2], [55, 0.5, 1],
])
y = np.array([
    0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1, 0, 1, 1
])
model = LogisticRegression()
model.fit(X, y)


def _score_single(last_donation, response_rate, total_donations):
    new_donor = np.array([[last_donation, response_rate, total_donations]])
    prob = model.predict_proba(new_donor)
    churn_prob = float(prob[0][1])
    return churn_prob


def _interpret(churn_prob, last_donation, response_rate, total_donations):
    if churn_prob >= 0.80:
        risk_level = "Very High"
    elif churn_prob >= 0.60:
        risk_level = "High"
    elif churn_prob >= 0.40:
        risk_level = "Moderate"
    elif churn_prob >= 0.20:
        risk_level = "Low"
    else:
        risk_level = "Very Low"
    reasons = []
    if last_donation > 60:
        reasons.append("Long gap since last donation")
    if response_rate < 0.4:
        reasons.append("Low responsiveness to communication")
    if total_donations < 3:
        reasons.append("Inexperienced or new donor")
    if not reasons:
        reasons.append("Donor seems engaged")
    return {
        "churn_probability": churn_prob,
        "risk_level": risk_level,
        "reasons": reasons,
    }


def predict_churn(data):
    """
    Accept either a single donor payload or a list of candidates:
    - If 'candidates' in data, expect a list of dicts with fields like last_donation, response_rate, total_donations, donor_id
    - Otherwise, accept last_donation, response_rate, total_donations for a single donor
    """
    if isinstance(data, dict) and 'candidates' in data:
        candidates = data.get('candidates') or []
        results = []
        for c in candidates:
            last = c.get('last_donation') or c.get('last_donation_days') or 90
            resp = c.get('response_rate') or c.get('response_rate_history') or 0.8
            total = c.get('total_donations') or c.get('donation_count') or 1
            prob = _score_single(last, resp, total)
            info = _interpret(prob, last, resp, total)
            item = {'donor_id': c.get('id') or c.get('donor_id'), 'score': prob}
            item.update(info)
            results.append(item)
        return results
    else:
        last_donation = data.get('last_donation', 30)
        response_rate = data.get('response_rate', 0.8)
        total_donations = data.get('total_donations', 3)
        prob = _score_single(last_donation, response_rate, total_donations)
        return _interpret(prob, last_donation, response_rate, total_donations)
