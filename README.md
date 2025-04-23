# Power Outage Tracking System - Updated Product Requirements Document

## 1. Project Overview

### 1.1 Purpose
The Power Outage Tracking System is a web application that allows users to record, track, and analyze power outages. The system consists of a Laravel backend API that serves data to a React frontend application. Users can log outages with associated weather data and receive detailed analytics about their power reliability.

### 1.2 Goals
- Enable users to track their power outages accurately
- Provide comprehensive analytics on outage patterns
- Correlate outages with weather conditions
- Offer insights for better preparation and planning

### 1.3 Tech Stack
- Backend: Laravel (v10.0)
- Authentication: Laravel Sanctum
- Database: SQLite
- API Standard: RESTful with JSON responses
- Weather Data: External Weather API integration

## 2. Functional Requirements

### 2.1 User Management

#### Authentication System
- Implement Laravel Sanctum for SPA authentication
- Support token-based authentication for API access
- Include email/password registration and login
- Secure logout functionality
- Password reset capability

#### User Model Requirements
```php
User {
    id: bigInteger (PK)
    name: string
    email: string (unique)
    password: string (hashed)
    email_verified_at: timestamp (nullable)
    remember_token: string (nullable)
    created_at: timestamp
    updated_at: timestamp
    
    // Relationships
    outages: hasMany
    locations: hasMany
}
```

### 2.2 Outage Management

#### Outage Model Requirements
```php
Outage {
    id: bigInteger (PK)
    user_id: bigInteger (FK to users)
    start_time: datetime
    end_time: datetime (nullable for ongoing outages)
    location_id: bigInteger (FK to locations)
    weather_condition: string
    temperature: float (Celsius)
    wind_speed: float (km/h)
    precipitation: float (mm)
    day_of_week: integer (0-6, where 0 = Sunday)
    is_holiday: boolean
    
    // Additional weather fields
    humidity: integer (percentage)
    pressure: float (mb)
    cloud: integer (percentage)
    
    created_at: timestamp
    updated_at: timestamp
    
    // Calculated attributes
    duration: integer (minutes, calculated on-the-fly)
    status: string ('ongoing' or 'completed')
    
    // Relationships
    user: belongsTo
    location: belongsTo
}
```

#### Location Model Requirements
```php
Location {
    id: bigInteger (PK)
    user_id: bigInteger (FK to users)
    name: string
    address: string
    locality: string (nullable)
    city: string (nullable)
    country: string (nullable)
    latitude: float
    longitude: float
    created_at: timestamp
    updated_at: timestamp
    
    // Calculated attributes
    full_address: string (formatted combination of all address fields)
    
    // Relationships
    user: belongsTo
    outages: hasMany
}
```

### 2.3 API Endpoints

#### Authentication Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/register` | Register new user |
| POST | `/api/v1/auth/login` | Login user (returns token) |
| POST | `/api/v1/auth/logout` | Logout user (invalidates token) |
| GET | `/api/v1/auth/user` | Get current authenticated user |

#### Outage Management Endpoints (Protected)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/outages` | List user's outages (paginated) |
| GET | `/api/v1/outages/{id}` | Get specific outage |
| POST | `/api/v1/outages` | Create new outage |
| PUT | `/api/v1/outages/{id}` | Update existing outage |
| DELETE | `/api/v1/outages/{id}` | Delete outage |
| PATCH | `/api/v1/outages/{id}/end` | End an ongoing outage |

#### Location Management Endpoints (Protected)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/locations` | List user's locations (paginated) |
| GET | `/api/v1/locations/{id}` | Get specific location |
| POST | `/api/v1/locations` | Create new location |
| PUT | `/api/v1/locations/{id}` | Update existing location |
| DELETE | `/api/v1/locations/{id}` | Delete location (if no associated outages) |

#### Analytics Endpoints (Protected)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/analytics/summary` | Overall statistics |
| GET | `/api/v1/analytics/trends` | Trends over time |
| GET | `/api/v1/analytics/daily` | Daily breakdown |
| GET | `/api/v1/analytics/monthly` | Monthly breakdown |
| GET | `/api/v1/analytics/peak-hours` | Peak outage times |
| GET | `/api/v1/analytics/weather-correlation` | Weather impact analysis |
| GET | `/api/v1/analytics/holiday-impact` | Holiday vs non-holiday |
| GET | `/api/v1/analytics/weekday-patterns` | Day of week analysis |

### 2.4 Query Parameters

#### Outage Filtering Parameters
- `start_date`: Filter outages after this date (YYYY-MM-DD)
- `end_date`: Filter outages before this date (YYYY-MM-DD)
- `duration_min`: Minimum duration in minutes
- `duration_max`: Maximum duration in minutes
- `status`: Filter by status ("ongoing" or "completed")
- `weather_condition`: Filter by weather condition
- `temperature_min`: Minimum temperature
- `temperature_max`: Maximum temperature
- `wind_speed_min`: Minimum wind speed
- `is_holiday`: Filter by holiday status
- `day_of_week`: Filter by day of week (0-6)

#### Location Filtering Parameters
- `city`: Filter by city name
- `locality`: Filter by locality name
- `country`: Filter by country name
- `latitude`, `longitude`, `radius`: Filter locations within a radius (in km)
- `search`: Search in name or address fields

#### Pagination Parameters
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

#### Sorting Parameters
- `sort_by`: Field to sort by (e.g., start_time, name, etc.)
- `order`: Sort order (asc, desc)

## 3. Technical Requirements

### 3.1 Duration and Status Calculation
- Duration should be calculated on-the-fly using `start_time` and `end_time`
- For ongoing outages (null end_time), calculate duration from start_time to current time
- Status should be calculated as 'ongoing' or 'completed' based on whether end_time is null
- Use Carbon for date/time calculations
- Return duration in minutes in API responses

### 3.2 Authentication & Authorization
- All endpoints except auth routes must be protected with `auth:sanctum` middleware
- Users can only access their own outage and location data
- Implement proper ownership checks via policies (OutagePolicy, LocationPolicy)
- Locations with associated outages cannot be deleted

### 3.3 Weather Data Integration
- Integrate with an external Weather API (likely WeatherAPI.com)
- Fetch and store weather data when creating or updating outages
- Store comprehensive weather data including humidity, pressure, and cloud coverage
- Implement error handling for weather service failures

### 3.4 Data Validation
- Validate all incoming data using Laravel Form Requests
- Ensure `end_time` is after `start_time`
- Validate coordinates (latitude/longitude)
- Implement duplicate location detection (same coordinates)
- Day of week should be auto-populated based on start_time

### 3.5 Performance
- Implement API response caching for analytics endpoints
- Use database indexing for frequently queried fields
- Use eager loading to prevent N+1 queries
- Provide comprehensive paginated responses with metadata

### 3.6 Security
- Implement rate limiting (60 requests per minute per user)
- Proper error handling and logging for security events

## 4. Implementation Notes

### 4.1 Migrations
Create migrations for:
1. Users table (use Laravel default)
2. Outages table with all specified fields
3. Locations table with user association
4. Personal access tokens table for Sanctum

### 4.2 Models
1. User model with Sanctum, HasFactory, and Notifiable traits
2. Outage model with relationships, calculated attributes, and query scopes
3. Location model with relationships, calculated attributes, and query scopes

### 4.3 Controllers
1. AuthController (register, login, logout, user)
2. OutageController (CRUD operations plus end functionality)
3. LocationController (CRUD operations)
4. AnalyticsController (all analytics endpoints)

### 4.4 Resources/Transformers
1. UserResource
2. OutageResource (include calculated duration and status)
3. LocationResource (include full_address)
4. CollectionResource for paginated responses
5. Analytics-related resources (pending implementation)

### 4.5 Services
1. WeatherService for interacting with external Weather API
2. Analytics service for data calculations (pending implementation)
