import sys
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
from sklearn.linear_model import LinearRegression, Lasso, Ridge

from statsmodels.tsa.holtwinters import ExponentialSmoothing
from sklearn.metrics import mean_absolute_error
from statsmodels.tsa.arima.model import ARIMA

def load_data(file_path):
    try:
        data = pd.read_csv(file_path)
        print(f"Dataset loaded successfully from {file_path}.")
        # print(f"Dataset Shape: {data.shape}")
        # print("First few rows:\n", data.head())
        return data
    except Exception as e:
        print(f"Error loading file: {e}")
        sys.exit(1)

def analyze_data(data):
    print("\n--- Dataset Analysis Done ---")
    # print("Columns:", data.columns)
    # print("Summary Statistics:\n", data.describe())
    # print("\nMissing Values:\n", data.isnull().sum())
    # print("\nData Types:\n", data.dtypes)

def forecast_time_series(data, column, forecast_period=5, model_type="holt_winters"):
    print("\n--- Forecasting ---")
    try:
        # Ensure the data is sorted by time (assuming a 'Date' column exists)
        if 'Date' in data.columns:
            data['Date'] = pd.to_datetime(data['Date'])
            data = data.sort_values('Date')
            data.set_index('Date', inplace=True)
        
        # Select the relevant column for forecasting
        if column not in data.columns:
            print(f"Column '{column}' not found in dataset.")
            return
        
        ts_data = data[column].dropna()
        print(f"Using '{column}' column for forecasting.")

        # Apply selected model
        if model_type == "holt_winters":
            model = ExponentialSmoothing(ts_data, trend="add", seasonal=None, damped_trend=True)
            fit_model = model.fit()
            forecast = fit_model.forecast(forecast_period)

        elif model_type == "arima":
            model = ARIMA(ts_data, order=(5, 1, 0))  # Example ARIMA model
            fit_model = model.fit()
            forecast = fit_model.forecast(forecast_period)
        
        # Print and plot forecast
        print(f"\nForecast for next {forecast_period} periods:")
        print(forecast)

        plt.figure(figsize=(12, 6))
        plt.plot(ts_data, label='Original Data', marker='o')
        plt.plot(fit_model.fittedvalues, label='Fitted Values', linestyle='--', color='orange')
        plt.plot(forecast, label='Forecast', marker='x', color='green')
        plt.title(f"Forecast for {column} using {model_type} Model")
        plt.legend()
        plt.show()

        # Evaluate model
        mae = mean_absolute_error(ts_data[-forecast_period:], forecast[:len(ts_data[-forecast_period:])])
        print(f"Mean Absolute Error of Forecast: {mae:.2f}")

    except Exception as e:
        print(f"Error in forecasting: {e}")

def main():
    if len(sys.argv) < 2:
        print("Usage: python forecast.py <csv_file_path>")
        sys.exit(1)

    file_path = sys.argv[1]

    # Load dataset
    data = load_data(file_path)

    # Automatically detect the target column (for example, the first numerical column after 'Date')
    numerical_columns = data.select_dtypes(include=[np.number]).columns
    column_name = numerical_columns[0] if len(numerical_columns) > 0 else None

    if column_name:
        print(f"Automatically selected column: {column_name}")
    else:
        print("No numerical column found for forecasting.")
        sys.exit(1)

    # Set default forecast period
    forecast_period = 5

    # Analyze dataset
    analyze_data(data)

    # Perform forecasting with Holt-Winters by default
    forecast_time_series(data, column_name, forecast_period, model_type="holt_winters")

if __name__ == "__main__":  
    main()
