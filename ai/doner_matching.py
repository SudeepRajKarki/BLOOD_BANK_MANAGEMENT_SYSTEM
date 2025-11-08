import math
import os
import requests
import re


def _haversine(lat1, lon1, lat2, lon2):
    R = 6371
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)
    a = (math.sin(dlat / 2) ** 2 +
         math.cos(math.radians(lat1)) *
         math.cos(math.radians(lat2)) *
         math.sin(dlon / 2) ** 2)
    c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))
    return R * c


def _fetch_donors_from_backend(backend_url, token=None):
    headers = {}
    if token:
        headers['Authorization'] = f'Bearer {token}'
    try:
        resp = requests.get(backend_url.rstrip('/') + '/api/donors', headers=headers, timeout=5)
        if resp.status_code == 200:
            return resp.json()
    except Exception:
        return []
    return []


def normalize_city(s):
    if not s:
        return None
    s = re.sub(r"[^a-zA-Z0-9 ]", "", s).strip()
    return s.title()


def match_donor(data):
    """
    Accepts either 'city' or 'location' as the receiver city.
    Returns a dict with key 'nearest_donors' which is a list of donor dicts.
    """
    # accept both keys for backward compatibility
    selected_city = data.get('city') or data.get('location') or data.get('city_name')
    if isinstance(selected_city, str):
        selected_city = normalize_city(selected_city)

    k = int(data.get('k', 5))

    city_coords = {
        'Kathmandu': [27.7172, 85.3240],
        'Lalitpur': [27.6644, 85.3188],
        'Bhaktapur': [27.6710, 85.4298],
        'Pokhara': [28.2096, 83.9856],
        'Butwal': [27.7000, 83.4500],
        'Biratnagar': [26.4525, 87.2718],
        'Hetauda': [27.4289, 85.0322],
        'Dharan': [26.8122, 87.2836],
        'Janakpur': [26.7083, 85.9230],
        'Birgunj': [27.0000, 84.8667],
        'Nepalgunj': [28.0583, 81.6174],
        'Mahendranagar': [29.0556, 80.5144],
        'Chitwan': [27.5292, 84.3542],
    }

    if not selected_city or selected_city not in city_coords:
        # return consistent structured response for callers
        return {'nearest_donors': [], 'error': 'Invalid or missing city'}

    donors = data.get('donors')
    if not donors:
        backend_url = data.get('backend_url') or os.environ.get('LARAVEL_URL')
        backend_token = data.get('backend_token') or os.environ.get('LARAVEL_TOKEN')
        if backend_url:
            donors = _fetch_donors_from_backend(backend_url, backend_token)
        else:
            # fall back to a small static sample if nothing provided
            donors = [
                {"id": 1, "name": "Donor A", "city": "Kathmandu", "location": [27.7172, 85.3240]},
                {"id": 2, "name": "Donor B", "city": "Lalitpur", "location": [27.6644, 85.3188]},
                {"id": 3, "name": "Donor C", "city": "Bhaktapur", "location": [27.6710, 85.4298]},
            ]

    processed = []
    for d in donors:
        donor_city = d.get('location') or d.get('city') or d.get('city_name')
        # donor_city might be a structured location or a string; normalize
        if isinstance(donor_city, list) and len(donor_city) >= 2:
            lat, lon = donor_city[0], donor_city[1]
        else:
            lat = d.get('latitude') or d.get('lat')
            lon = d.get('longitude') or d.get('lon')
            if (not lat or not lon) and donor_city:
                donor_city = normalize_city(donor_city)
                if donor_city in city_coords:
                    lat, lon = city_coords[donor_city]

        if lat is None or lon is None:
            continue

        try:
            processed.append({
                'id': d.get('id'),
                'name': d.get('name') or d.get('email'),
                'city': donor_city,
                'location': [float(lat), float(lon)],
                'blood_group': d.get('blood_group'),
                'last_donation_date': d.get('last_donation_date'),
            })
        except Exception:
            continue

    receiver = city_coords[selected_city]
    distances = []
    for donor in processed:
        donor_loc = donor['location']
        distance = _haversine(receiver[0], receiver[1], donor_loc[0], donor_loc[1])
        distances.append({
            'id': donor.get('id'),
            'name': donor.get('name'),
            'city': donor.get('city'),
            'distance_km': distance,
            'blood_group': donor.get('blood_group'),
        })

    nearest = sorted(distances, key=lambda x: x['distance_km'])[:k]
    return {'nearest_donors': nearest}
