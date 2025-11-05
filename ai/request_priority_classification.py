
import math
from collections import defaultdict

train_data = [
    ("I need blood urgently", "High"),
    ("Patient is in critical condition", "High"),
    ("Please help quickly", "High"),
    ("Urgent requirement for O+ blood", "High"),
    ("Blood needed immediately due to accident", "High"),
    ("Emergency case, please respond fast", "High"),
    ("Blood required within 24 hours", "Medium"),
    ("Can arrange tomorrow", "Medium"),
    ("Surgery scheduled tomorrow, need blood", "Medium"),
    ("Required for planned operation", "Medium"),
    ("Can donate next morning", "Medium"),
    ("Blood needed by tomorrow noon", "Medium"),
    ("Not an emergency", "Low"),
    ("Need blood after 2 days", "Low"),
    ("Blood required for health check next week", "Low"),
    ("No urgency in donation", "Low"),
    ("Routine request, no emergency", "Low"),
    ("Low priority for blood needed", "Low")
]

import re


def tokenize(text):
    if not text:
        return []
    # remove punctuation and split
    text = re.sub(r"[^a-zA-Z0-9\s]", " ", text)
    return [t for t in text.lower().split() if t]

class_word_counts = defaultdict(lambda: defaultdict(int))
class_doc_counts = defaultdict(int)
vocab = set()
total_docs = 0
for text, label in train_data:
    tokens = tokenize(text)
    for token in tokens:
        class_word_counts[label][token] += 1
        vocab.add(token)
    class_doc_counts[label] += 1
    total_docs += 1
priors = {label: class_doc_counts[label] / total_docs for label in class_doc_counts}
word_probs = {}
for label in class_word_counts:
    total_words = sum(class_word_counts[label].values())
    word_probs[label] = {}
    for word in vocab:
        word_probs[label][word] = (class_word_counts[label][word] + 1) / (total_words + len(vocab))

def predict_priority(data):
    text = data.get("reason", "")
    tokens = tokenize(text)
    # quick rule-based override for clear emergency keywords
    emergency_keywords = {'emergency', 'urgent', 'accident', 'heavy', 'bleed', 'bleeding', 'critical', 'surgery', 'immediately'}
    if any(k in tokens for k in emergency_keywords):
        return {"priority": "High"}
    scores = {}
    for label in priors:
        score = math.log(priors[label])
        for token in tokens:
            if token in vocab:
                score += math.log(word_probs[label][token])
        scores[label] = score
    result = max(scores, key=scores.get)
    return {"priority": result}
