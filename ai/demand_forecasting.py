
import pandas as pd
from prophet import Prophet

def forecast_demand(data=None):
    # Optionally accept custom data, else use default
    if data and "history" in data:
        df = pd.DataFrame(data["history"])
    else:
        df = pd.DataFrame({
            'ds': pd.date_range(start='2024-01-01', periods=100, freq='D'),
            'y': [20 + (i % 10) + (i * 0.2) for i in range(100)]
        })
    model = Prophet()
    model.fit(df)
    future = model.make_future_dataframe(periods=30)
    forecast = model.predict(future)
    # Return only the forecasted values for the next 30 days
    result = forecast[['ds', 'yhat']].tail(30).to_dict(orient='records')
    return {"forecast": result}
