import re
import nltk
import string
import pickle
import pandas as pd
from nltk.corpus import stopwords
from nltk.stem.porter import PorterStemmer
from nltk.tokenize import RegexpTokenizer


def processTweet(tweet):
    #Convert to lower case
    tweet = str(tweet).lower()
    tweet = re.sub(r'-',' ',str(tweet))
    tweet = re.sub(r"(\d+)(st|nd|rd|th)\b",'',str(tweet))
    tweet = re.sub('[0-9]+', '', str(tweet))
    tweet = re.sub(r'^rt[\s]+', '', str(tweet), flags=re.MULTILINE)  # removes RT
    tweet = filter(lambda x: x in string.printable, str(tweet))  # filter non-ascii characers
    #Remove Urls
    tweet = re.sub('((www\.[^\s]+)|(https?://[^\s]+))','',str(tweet))
    #Convert @username to AT_USER
    tweet = re.sub('@[^\s]+','',str(tweet))
    #Remove additional white spaces
    tweet = re.sub('[\s]+', ' ',str(tweet))
    #Replace #word with word
    tweet = re.sub(r'#([^\s]+)', r'\1',str(tweet))
    #remove extra white space
    tweet = re.sub('[\s]+', ' ', str(tweet))
    #print(tweet)
    tweet = re.sub(r'[^\w\s]','',str(tweet))
    #trim
    tweet = tweet.strip()
    return tweet

def feature_extract(tweet):
    #convert to lowercase
    tweet = str(tweet).lower()
    #print(tweet)
    #tweet = tweet.translate(None, string.punctuation)
    #tokenize the words
    tokenizer = RegexpTokenizer(r'\w+')
    words = tokenizer.tokenize(tweet)
    #print(words)
    #only keep alphanumeric words
    words = [word for word in words if word.isalpha()]
    #print(words)
    #remove stop words
    stop_words = set(stopwords.words('english'))
    words = [w for w in words if not w in stop_words]
    #print(words)
    #stemwords
    # porter = PorterStemmer()
    # words = [porter.stem(word) for word in words]
    # print(words)
    #freq distribution of words
    words = nltk.FreqDist(words)
    #print(dict(words))
    #create bigrams of words
    wordslist = nltk.bigrams(tweet.split())
    #print(dict(wordslist))
    wordslist = nltk.FreqDist(wordslist)    
    #print(dict(wordslist))
    features = words+wordslist
    #print dict(features)
    return dict(features)

positive = pd.read_csv('positive2.csv',names=(['sentiment','text']),encoding='latin1')
postweets = positive['text'].values.tolist()

negative = pd.read_csv('negative2.csv',names=(['sentiment','text']),encoding='latin1')
negtweets = negative['text'].values.tolist()

neutral = pd.read_csv('neutral2.csv',names=(['sentiment','text']),encoding='latin1')
neutweets = neutral['text'].values.tolist()

neg_features = [(feature_extract(processTweet(str(tweet.encode('utf-8').strip()))), 'neg') for tweet in negtweets]
pos_features = [(feature_extract(processTweet(str(tweet.encode('utf-8').strip()))), 'pos') for tweet in postweets]
neu_features = [(feature_extract(processTweet(str(tweet.encode('utf-8').strip()))), 'neu') for tweet in neutweets]
print len(neg_features)
print len(pos_features)
print len(neu_features)
train_features = neg_features[:3100] + pos_features[:1890]  + neu_features[:2480]
#print train_features
test_features = neg_features[3101:] + pos_features[1891:]  + neu_features[2481:]

classifier = nltk.classify.NaiveBayesClassifier.train(train_features)

print 'accuracy:', nltk.classify.util.accuracy(classifier, test_features)
classifier.show_most_informative_features()
test_result = []
gold_result = []

from nltk.metrics import ConfusionMatrix

for i in range(len(test_features)):
    test_result.append(classifier.classify(test_features[i][0]))
    gold_result.append(test_features[i][1])

CM = nltk.ConfusionMatrix(gold_result, test_result)
print(CM)

with open('sentiment2.pickle', 'wb') as f:
    pickle.dump(classifier, f)