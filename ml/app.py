from flask import Flask, request, jsonify
from flask_cors import CORS
import joblib
import os

app = Flask(__name__)
CORS(app)

# Load models
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
symptom_model = joblib.load(os.path.join(BASE_DIR, 'models/symptom_classifier.pkl'))
myth_model    = joblib.load(os.path.join(BASE_DIR, 'models/myth_detector.pkl'))

# Symptom to database search term mapping
SYMPTOM_MAP = {
    'fever':                'Fever',
    'diarrhoea':            'Diarrhoea',
    'cough':                'Cough',
    'headache':             'Headache',
    'chest_pain':           'Chest Pain',
    'difficulty_breathing': 'Difficulty Breathing',
    'vomiting':             'Vomiting',
    'abdominal_pain':       'Abdominal Pain',
    'swollen_legs':         'Swollen Legs',
    'skin_rash':            'Skin Rash',
    'thirst_urination':     'Excessive Thirst and Frequent Urination',
    'sadness':              'Prolonged Sadness or Loss of Interest',
}

# ── Health check ──────────────────────────────
@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ML API is running', 'models': ['symptom_classifier', 'myth_detector']})

# ── Symptom Classifier ────────────────────────
@app.route('/classify-symptom', methods=['POST'])
def classify_symptom():
    data = request.get_json()
    text = data.get('text', '').strip()

    if not text:
        return jsonify({'error': 'No text provided'}), 400

    prediction    = symptom_model.predict([text])[0]
    probabilities = symptom_model.predict_proba([text])[0]
    confidence    = round(float(max(probabilities)) * 100, 2)

    # Top 3 predictions

    classes      = symptom_model.classes_
    top_indices  = probabilities.argsort()[-3:][::-1]
    top_3 = [
        {
            'symptom':    SYMPTOM_MAP.get(classes[i], classes[i]),
            'key':        classes[i],
            'confidence': round(float(probabilities[i]) * 100, 2)
        }
        for i in top_indices
    ]

    return jsonify({
        'input':          text,
        'predicted':      prediction,
        'db_search_term': SYMPTOM_MAP.get(prediction, prediction),
        'confidence':     confidence,
        'top_3':          top_3
    })

# ── Myth Detector ─────────────────────────────
@app.route('/detect-myth', methods=['POST'])
def detect_myth():
    data = request.get_json()
    text = data.get('text', '').strip()

    if not text:
        return jsonify({'error': 'No text provided'}), 400

    prediction    = myth_model.predict([text])[0]
    probabilities = myth_model.predict_proba([text])[0]
    confidence    = round(float(max(probabilities)) * 100, 2)

    classes   = myth_model.classes_
    myth_idx  = list(classes).index('myth') if 'myth' in classes else 0
    myth_prob = round(float(probabilities[myth_idx]) * 100, 2)

    return jsonify({
        'input':            text,
        'is_myth':          prediction == 'myth',
        'label':            prediction,
        'confidence':       confidence,
        'myth_probability': myth_prob,
        'message':          'This appears to be a health myth.' if prediction == 'myth' else 'This appears to be accurate health information.'
    })

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)