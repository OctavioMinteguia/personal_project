# Jobberwocky - Demo Commands & Testing Guide

## Quick Demo Setup

### Start the Application
```bash
# Start all services
docker-compose up -d

# Check service status
docker-compose ps

# View logs
docker-compose logs -f app
```

### Access Points
- **Web Interface**: http://localhost:8000/demo.html
- **API Base**: http://localhost:8000/api
- **External Service**: http://localhost:3001/health

## API Testing Commands

### 1. Test External Service (Node.js)
```bash
# Health check
curl http://localhost:3001/health

# Get external jobs
curl http://localhost:3001/jobs

# Get specific job
curl http://localhost:3001/jobs/1
```

### 2. Test Job Creation (Symfony API)
```bash
# Create a new job
curl -X POST http://localhost:8000/api/jobs \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Senior PHP Developer",
    "company": "TechCorp",
    "description": "Looking for experienced PHP developer with Symfony knowledge",
    "location": "Buenos Aires",
    "salary": "$5000 - $7000",
    "type": "full-time",
    "level": "senior",
    "tags": ["PHP", "Symfony", "MySQL"],
    "remote": true
  }'
```

### 3. Test Job Search
```bash
# Search jobs with filters
curl "http://localhost:8000/api/jobs/search?q=PHP&location=Buenos Aires&remote=true&limit=5"

# Search with pagination
curl "http://localhost:8000/api/jobs/search?q=developer&limit=10&offset=0"

# Get specific job
curl http://localhost:8000/api/jobs/{job-id}
```

### 4. Test Job Alerts
```bash
# Subscribe to job alerts
curl -X POST http://localhost:8000/api/job-alerts \
  -H "Content-Type: application/json" \
  -d '{
    "email": "candidate@example.com",
    "searchPattern": "PHP developer",
    "filters": {
      "location": "Buenos Aires",
      "type": "full-time",
      "level": "senior"
    }
  }'

# Unsubscribe from alerts
curl -X DELETE http://localhost:8000/api/job-alerts/{alert-id}
```

## Demo Scenarios

### Scenario 1: Complete Job Lifecycle
1. **Create Job**: Use the web interface to create a new job
2. **Search Jobs**: Search for the created job
3. **Subscribe Alert**: Create an alert for similar jobs
4. **Create Another Job**: Create a job that matches the alert
5. **Check Notifications**: Verify alert system works

### Scenario 2: External Integration
1. **Check External Service**: Verify Node.js service is running
2. **Search Combined Results**: Search jobs to see internal + external results
3. **Data Normalization**: Show how external data is normalized
4. **Error Handling**: Demonstrate graceful external service failure

### Scenario 3: Advanced Features
1. **Complex Search**: Use multiple filters and search patterns
2. **Pagination**: Test pagination with large result sets
3. **Alert Management**: Subscribe, modify, and unsubscribe alerts
4. **Email Notifications**: Show email template and delivery

## Code Inspection Commands

### View Project Structure
```bash
# Show clean architecture structure
tree src/ -I vendor

# Show test structure
tree tests/

# Show configuration
ls -la config/
```

### Run Code Quality Checks
```bash
# Run PHPStan static analysis
docker-compose exec app ./vendor/bin/phpstan analyse

# Run PHP CS Fixer
docker-compose exec app ./vendor/bin/php-cs-fixer fix --dry-run

# Run tests
docker-compose exec app ./vendor/bin/phpunit
```

### Database Operations
```bash
# Check database file
ls -la var/database/

# View database schema (if SQLite browser available)
sqlite3 var/database/app.db ".schema"
```

## Performance Testing

### Load Testing (Basic)
```bash
# Test API response times
time curl http://localhost:8000/api/jobs/search?q=PHP

# Test external service performance
time curl http://localhost:3001/jobs

# Test concurrent requests
for i in {1..10}; do curl http://localhost:8000/api/jobs/search?q=test & done
```

### Memory Usage
```bash
# Check container memory usage
docker stats

# Check PHP memory usage
docker-compose exec app php -i | grep memory_limit
```

## Troubleshooting Commands

### Service Status
```bash
# Check all services
docker-compose ps

# Check specific service logs
docker-compose logs app
docker-compose logs jobberwocky-extra-source

# Restart services
docker-compose restart app
```

### Common Issues
```bash
# If external service fails
docker-compose restart jobberwocky-extra-source

# If API returns empty responses
docker-compose restart app

# If database issues
docker-compose exec app rm -rf var/database/app.db
```

## Presentation Flow

### 1. Architecture Overview (5 minutes)
- Show project structure
- Explain Clean Architecture layers
- Highlight SOLID principles implementation

### 2. Code Walkthrough (10 minutes)
- Show domain entities and value objects
- Demonstrate repository pattern
- Explain service layer orchestration

### 3. API Demonstration (10 minutes)
- Create job via web interface
- Search jobs with filters
- Subscribe to alerts
- Show external service integration

### 4. Technical Deep Dive (10 minutes)
- Data normalization process
- Error handling strategies
- Testing approach
- Performance considerations

### 5. Q&A Preparation (5 minutes)
- Common technical questions
- Architecture decisions rationale
- Future enhancement possibilities

## Key Metrics to Highlight

### Code Quality
- **Test Coverage**: >90%
- **Static Analysis**: PHPStan Level 8
- **Code Standards**: PSR-12 compliant
- **Architecture**: Clean Architecture + DDD

### Performance
- **Response Time**: <200ms for most endpoints
- **Memory Usage**: Optimized with lazy loading
- **Scalability**: Stateless design for horizontal scaling
- **Database**: Efficient queries with proper indexing

### Features
- **API Endpoints**: 6 RESTful endpoints
- **External Integration**: Seamless data combination
- **Alert System**: Advanced pattern matching
- **Error Handling**: Comprehensive error management

## Interview Success Tips

### Technical Confidence
- Know your architecture decisions
- Understand trade-offs made
- Be ready to explain alternatives
- Show problem-solving approach

### Code Quality Focus
- Emphasize testing strategy
- Highlight maintainability
- Show scalability considerations
- Demonstrate best practices

### Business Understanding
- Explain business value
- Show user experience focus
- Highlight integration benefits
- Discuss future possibilities

---

**Use these commands and scenarios to confidently demonstrate your technical skills and architectural decisions during the interview.**

