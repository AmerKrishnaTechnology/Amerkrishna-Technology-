from flask import Flask, request, jsonify
from flask_cors import CORS
import nltk
from nltk.tokenize import sent_tokenize, word_tokenize
from nltk.corpus import stopwords, wordnet
from nltk.tag import pos_tag
from collections import defaultdict
import random
import json
import re

app = Flask(__name__)
CORS(app)

# Download required NLTK data
nltk.download('punkt')
nltk.download('stopwords')
nltk.download('averaged_perceptron_tagger')
nltk.download('wordnet')

# Load industry-specific templates
with open('templates.json', 'r') as f:
    templates = json.load(f)

def get_synonyms(word):
    synonyms = []
    for syn in wordnet.synsets(word):
        for lemma in syn.lemmas():
            if lemma.name() != word and '_' not in lemma.name():
                synonyms.append(lemma.name())
    return list(set(synonyms))[:3]

def analyze_keywords(keywords):
    words = keywords.split()
    analysis = {
        'main_topic': words[0] if words else '',
        'related_terms': [],
        'industry': 'general'
    }
    
    for word in words:
        synonyms = get_synonyms(word)
        if synonyms:
            analysis['related_terms'].extend(synonyms)
    
    return analysis

def generate_paragraph(topic, style='formal'):
    intro_templates = templates['paragraphs']['intro']
    body_templates = templates['paragraphs']['body']
    conclusion_templates = templates['paragraphs']['conclusion']
    
    intro = random.choice(intro_templates).format(topic=topic)
    body = random.choice(body_templates).format(topic=topic)
    conclusion = random.choice(conclusion_templates).format(topic=topic)
    
    return f"{intro}\n\n{body}\n\n{conclusion}"

@app.route('/api/suggest', methods=['POST'])
def get_suggestions():
    data = request.json
    content = data.get('content', '')
    content_type = data.get('contentType', 'blog')
    keywords = data.get('keywords', '')

    # Analyze existing content
    sentences = sent_tokenize(content)
    words = word_tokenize(content.lower())
    stop_words = set(stopwords.words('english'))
    
    # Get important words
    important_words = [word for word in words 
                      if word not in stop_words 
                      and word.isalnum()]
    
    # Generate contextual suggestions
    suggestions = []
    keyword_analysis = analyze_keywords(keywords)
    
    if len(words) > 5:
        # Add topic-specific suggestions
        for template in templates[content_type]:
            suggestion = template.format(
                topic=keyword_analysis['main_topic'],
                related=random.choice(keyword_analysis['related_terms']) if keyword_analysis['related_terms'] else ''
            )
            suggestions.append(suggestion)
        
        # Add contextual suggestions based on content
        if len(sentences) > 0:
            last_sentence = sentences[-1]
            pos_tags = pos_tag(word_tokenize(last_sentence))
            
            # Generate follow-up suggestions
            if any(tag[1].startswith('VB') for tag in pos_tags):
                suggestions.append(f"Consider expanding on how {keyword_analysis['main_topic']} affects...")
            elif any(tag[1].startswith('NN') for tag in pos_tags):
                suggestions.append(f"You might want to explain more about {keyword_analysis['main_topic']}'s features...")

    return jsonify({
        'suggestions': suggestions,
        'analysis': keyword_analysis
    })

@app.route('/api/generate', methods=['POST'])
def generate_content():
    data = request.json
    content_type = data.get('contentType', 'blog')
    keywords = data.get('keywords', '')
    
    # Analyze keywords and generate structured content
    analysis = analyze_keywords(keywords)
    
    # Generate content based on type
    if content_type == 'blog':
        generated = generate_paragraph(analysis['main_topic'], 'casual')
    elif content_type == 'article':
        generated = generate_paragraph(analysis['main_topic'], 'formal')
    elif content_type == 'social':
        template = random.choice(templates['social'])
        generated = template.format(
            topic=analysis['main_topic'],
            related=random.choice(analysis['related_terms']) if analysis['related_terms'] else ''
        )
    else:
        generated = generate_paragraph(analysis['main_topic'])

    return jsonify({
        'content': generated,
        'suggestions': analysis['related_terms'],
        'analysis': {
            'topic': analysis['main_topic'],
            'style': content_type,
            'keywords': list(set(keywords.split()))
        }
    })

if __name__ == '__main__':
    app.run(port=5000, debug=True)