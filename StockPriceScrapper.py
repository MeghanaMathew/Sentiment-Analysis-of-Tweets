today = datetime.datetime.now().date()
yesterday = today - datetime.timedelta(1)
mydata = quandl.get("NSE/INDIGO", start_date=yesterday, end_date=today)
mydata = mydata.reset_index()
todaysprice = mydata.iloc[0]['Open']
cnx = mysql.connector.connect(user='root', password='',host='localhost',database='sentiment')
df = pd.read_sql('SELECT * FROM data where company="INDIGO"', con=cnx)
changed=[]
for i in range(len(df)):
   if numpy.isnan(df.stock_price.iloc[i]):
      changed.append(i)
      df.stock_price.iloc[i]=(df.stock_price.iloc[i-1]+todaysprice)/2
for i in range(len(df)):
   if numpy.isnan(df.stock_direction.iloc[i]):
      diff = df.stock_price.iloc[i]-df.stock_price.iloc[i-1]
      if diff>0:
	df.stock_direction.iloc[i] = 1
      elif diff<0:
	df.stock_direction.iloc[i] = -1
      else:
	df.stock_direction.iloc[i] = 0
x = cnx.cursor()
for i in range(len(changed)):
   sql = "update data set stock_price = %s,stock_direction=%s where id = %d" % (df.stock_price.iloc[changed[i]],df.stock_direction.iloc[changed[i]],df.id.iloc[changed[i]])
    x.execute(sql)
