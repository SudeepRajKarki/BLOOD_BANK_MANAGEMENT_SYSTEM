from fastapi import FastAPI, Request
from doner_matching import match_donor
from request_priority_classification import predict_priority
from doner_chrun_prediction import predict_churn
from demand_forecasting import forecast_demand
from campaign_targeting import recommend_campaign

app = FastAPI()

@app.post("/match-donor")
async def match_donor_endpoint(request: Request):
    data = await request.json()
    result = match_donor(data)
    return result

@app.post("/predict-priority")
async def predict_priority_endpoint(request: Request):
    data = await request.json()
    result = predict_priority(data)
    return result

@app.post("/churn-predict")
async def churn_predict_endpoint(request: Request):
    data = await request.json()
    result = predict_churn(data)
    return result

@app.post("/demand-forecast")
async def demand_forecast_endpoint(request: Request):
    data = await request.json()
    result = forecast_demand(data)
    return result

@app.post("/campaign-targeting")
async def campaign_targeting_endpoint(request: Request):
    data = await request.json()
    result = recommend_campaign(data)
    return result
