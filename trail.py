import numpy as np
import pandas as pd
import os
import datetime
import matplotlib.pyplot as plt
import seaborn as sns
from IPython.display import display
from sklearn.preprocessing import LabelEncoder
from sklearn.model_selection import train_test_split, cross_val_score, RandomizedSearchCV
from sklearn.linear_model import LinearRegression, Lasso, Ridge
from sklearn.tree import DecisionTreeRegressor
from sklearn.ensemble import RandomForestRegressor, ExtraTreesRegressor
from catboost import CatBoostRegressor
from lightgbm import LGBMRegressor
from xgboost import XGBRegressor
from sklearn.metrics import r2_score
from scipy.stats import uniform, randint
import pickle
import warnings
warnings.filterwarnings('ignore')

# Load data
df = pd.read_csv('Train.csv')

# Display number of unique values per column
df.apply(lambda x: len(x.unique()))

# Check for duplicates
df.duplicated().sum()

# Check for missing values
df.isnull().sum()

# Display column types and info
df.info()

# Identify categorical columns
cat_col = []
for x in df.dtypes.index:
    if df.dtypes[x] == 'object':
        cat_col.append(x)
display(cat_col)

# Remove specific columns from categorical list
cat_col.remove('Item_Identifier')
cat_col.remove('Outlet_Identifier')
display(cat_col)

# Display unique values count for each categorical column
for col in cat_col:
    print(col, len(df[col].unique()))

# Display value counts for each categorical column
for col in cat_col:
    print(col)
    print(df[col].value_counts(), '\n')
    print('-' * 55)

# Handle missing values for 'Item_Weight'
miss_bool = df['Item_Weight'].isnull()
Item_Weight_Null = df[df['Item_Weight'].isnull()]
display(Item_Weight_Null)
Item_Weight_Mean = df.pivot_table(values='Item_Weight', index='Item_Identifier')
display(Item_Weight_Mean)

# Fill missing 'Item_Weight' with mean based on 'Item_Identifier'
for i, item in enumerate(df['Item_Identifier']):
    if miss_bool[i]:
        if item in Item_Weight_Mean.index:
            df.at[i, 'Item_Weight'] = Item_Weight_Mean.loc[item, 'Item_Weight']
        else:
            df.at[i, 'Item_Weight'] = np.mean(df['Item_Weight'])

# Confirm missing values are handled
df['Item_Weight'].isna().sum()

# Handle missing values for 'Outlet_Size'
df['Outlet_Size'].value_counts()
df['Outlet_Size'].isnull().sum()
Outlet_Size_Null = df[df['Outlet_Size'].isna()]
display(Outlet_Size_Null)

# Fill missing 'Outlet_Size' based on 'Outlet_Type'
Outlet_Size_Mode = df.pivot_table(values='Outlet_Size', columns='Outlet_Type', aggfunc=lambda x: x.mode()[0])
display(Outlet_Size_Mode)
df.loc[df['Outlet_Size'].isna(), 'Outlet_Size'] = df.loc[df['Outlet_Size'].isna(), 'Outlet_Type'].apply(lambda x: Outlet_Size_Mode[x])

# Confirm missing 'Outlet_Size' is handled
df['Outlet_Size'].isna().sum()

# Handle 'Item_Visibility' being 0
df['Item_Visibility'].replace(0, df['Item_Visibility'].mean(), inplace=True)
sum(df['Item_Visibility'] == 0)

# Handle 'Item_Fat_Content' encoding
df['Item_Fat_Content'].value_counts()
df['Item_Fat_Content'] = df['Item_Fat_Content'].replace({'LF': 'Low Fat', 'low fat': 'Low Fat', 'reg': 'Regular'})
df['Item_Fat_Content'].value_counts()

# Create new item type column
df['New_Item_Type'] = df['Item_Identifier'].apply(lambda x: x[:2])
df['New_Item_Type'].value_counts()

# Replace item types
df['New_Item_Type'] = df['New_Item_Type'].replace({'FD': 'Food', 'NC': 'Non-Consumables', 'DR': 'Drinks'})
df['New_Item_Type'].value_counts()

# Adjust 'Item_Fat_Content' for 'Non-Consumables'
df.loc[df['New_Item_Type'] == 'Non-Consumables', 'Item_Fat_Content'] = 'Non-Edible'
df['Item_Fat_Content'].value_counts()

# Handle outlet establishment year and create outlet years column
df['Outlet_Establishment_Year'].unique()
curr_time = datetime.datetime.now()
df['Outlet_Years'] = df['Outlet_Establishment_Year'].apply(lambda x: curr_time.year - x)

# Plot some visualizations
sns.countplot(x=df['Item_Fat_Content'])
plt.title('Count of Item_Fat_Content')
plt.savefig('Count of Item_Fat_Content.png')
plt.show()

# ... other plots for different columns as in your code ...

# Filter numeric columns for correlation
numeric_df = df.select_dtypes(include=[np.number])

# Now compute the correlation matrix
sns.heatmap(numeric_df.corr(), cmap='binary', cbar=True, annot=True, square=True)
plt.title('Correlation Heat Map')
plt.savefig('Correlation Heat Map.png')
plt.show()

# Label Encoding
le = LabelEncoder()
df['Outlet'] = le.fit_transform(df['Outlet_Identifier'])

# Encode categorical columns
cat_col = ['Item_Fat_Content', 'Item_Type', 'Outlet_Size', 'Outlet_Location_Type', 'Outlet_Type', 'New_Item_Type']
for col in cat_col:
    df[col] = le.fit_transform(df[col])

# One-hot encoding for some categorical columns
df = pd.get_dummies(df, columns=['Item_Fat_Content', 'Outlet_Size', 'Outlet_Location_Type', 'Outlet_Type', 'New_Item_Type'])

# Prepare features and target
x = df.drop(['Item_Identifier', 'Outlet_Identifier', 'Outlet_Establishment_Year', 'Item_Outlet_Sales'], axis=1)
y = df['Item_Outlet_Sales']

# Train-test split
x_train, x_test, y_train, y_test = train_test_split(x, y, test_size=0.3, random_state=10)

# Define and train models (example with Linear Regression)
model = LinearRegression()
model.fit(x_train, y_train)
prediction = model.predict(x_test)

# Evaluate model
print('R2 Score:', r2_score(y_test, prediction))

# Create a DataFrame to display actual vs predicted values for the test set
forecast_df = pd.DataFrame({'Actual': y_test, 'Predicted': prediction})

# Display previous results (test set) with a label
print("Previous Results (Test Set):")
display(forecast_df)

# Save model using pickle
pickle.dump(model, open('Model.pkl', 'wb'))

# Load model from file
loaded_model = pickle.load(open('Model.pkl', 'rb'))
fpred = loaded_model.predict(x)

# Create a DataFrame to display actual vs predicted values for the full dataset
full_data_forecast_df = pd.DataFrame({'Actual': y, 'Predicted': fpred})

# Display future forecast values (entire dataset) with a label
print("Future Forecast Values (Entire Dataset):")
display(full_data_forecast_df)

# Print R2 score for full data predictions
print('R2 Score of Full Data:', r2_score(y, fpred))
