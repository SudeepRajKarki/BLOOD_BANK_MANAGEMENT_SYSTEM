import pandas as pd
from prophet import Prophet

def forecast_demand(data=None):
    # Prepare sample or custom data
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

    # Extract only the next 30 days
    result = forecast[['ds', 'yhat']].tail(30)
    result['ds'] = result['ds'].dt.strftime('%b %d, %Y')  # prettier date
    result['yhat'] = result['yhat'].round(1)              # one decimal place

    # Create human-friendly interpretation
    avg_demand = result['yhat'].mean().round(1)
    trend = "increasing" if result['yhat'].iloc[-1] > result['yhat'].iloc[0] else "decreasing"

    summary = {
        "summary": f"Average expected daily blood demand is around {avg_demand} units. The demand trend appears to be {trend} over the next 30 days.",
        "forecast_table": result.to_dict(orient='records')
    }

    return summary
