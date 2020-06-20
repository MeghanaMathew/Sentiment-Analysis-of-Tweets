consumer_key = 'XXXXXXXXXXXXXXXXX '                                   
consumer_secret = 'XXXXXXXXXXXXXXXXX '
access_token = ' XXXXXXXXXXXXXXXXX'
access_token_secret = ' XXXXXXXXXXXXXXXXX'
auth = tweepy.OAuthHandler(consumer_key, consumer_secret)
auth.set_access_token(access_token, access_token_secret)
api = tweepy.API(auth,wait_on_rate_limit=True)
csvFile = open('indigo.csv', 'a')
csvWriter = csv.writer(csvFile)
For tweet in tweepy.Cursor(api.search,q="#indigo",,lang="en",since="2017-01-03").items():
    print (tweet.created_at, tweet.text)
    csvWriter.writerow([tweet.created_at, tweet.text.encode('utf-8')])
