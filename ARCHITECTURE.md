# Jobberwocky - Technical Architecture Deep Dive

## 🏗️ Architecture Patterns Implementation

### 1. Clean Architecture Layers

#### Domain Layer (Core Business Logic)
- **Entities**: `Job`, `JobAlert` - Rich domain models with business rules
- **Value Objects**: Type-safe objects (`JobTitle`, `Email`, `Salary`) preventing invalid states
- **Domain Services**: Business logic that doesn't belong to entities
- **Repository Interfaces**: Contracts for data access

#### Application Layer (Use Cases)
- **Services**: Orchestrate domain objects and infrastructure
- **DTOs**: Data transfer objects for API communication
- **Interfaces**: Define contracts for external services

#### Infrastructure Layer (External Concerns)
- **Controllers**: Handle HTTP requests/responses
- **Repositories**: Implement data persistence
- **External Clients**: Integrate with third-party services
- **Email Services**: Handle notifications

### 2. SOLID Principles Implementation

#### Single Responsibility Principle (SRP)
- Each class has one reason to change
- Controllers handle HTTP, Services handle business logic
- Repositories handle data access

#### Open/Closed Principle (OCP)
- Open for extension, closed for modification
- Repository interfaces allow different implementations
- Service interfaces enable different strategies

#### Liskov Substitution Principle (LSP)
- Implementations can be substituted without breaking functionality
- Repository implementations are interchangeable

#### Interface Segregation Principle (ISP)
- Small, focused interfaces
- `JobRepositoryInterface` vs `JobAlertRepositoryInterface`

#### Dependency Inversion Principle (DIP)
- High-level modules don't depend on low-level modules
- Both depend on abstractions (interfaces)

## 🔄 Data Flow Architecture

### Job Creation Flow
1. **HTTP Request** → `JobController`
2. **Validation** → Value Objects creation
3. **Business Logic** → `JobService::createJob()`
4. **Persistence** → `DoctrineJobRepository::save()`
5. **Notification** → `JobAlertService::notifyNewJob()`
6. **Email Dispatch** → `SymfonyMailerService`

### Job Search Flow
1. **HTTP Request** → `JobController::search()`
2. **Internal Search** → `JobService::searchJobs()`
3. **External Search** → `JobberwockyExtraSourceClient::fetchJobs()`
4. **Data Normalization** → Convert external format to standard
5. **Result Combination** → Merge internal and external results
6. **Response** → JSON with pagination

## 🎯 Design Patterns Used

### Repository Pattern
```php
interface JobRepositoryInterface
{
    public function save(Job $job): void;
    public function findById(string $id): ?Job;
    public function findByCriteria(array $criteria): array;
}
```

### Factory Pattern
```php
class Job
{
    public static function create(
        JobTitle $title,
        CompanyName $company,
        JobDescription $description
    ): self {
        // Factory method for creating jobs
    }
}
```

### Adapter Pattern
```php
class JobberwockyExtraSourceClient implements JobSourceClientInterface
{
    // Adapts external service to internal interface
}
```

### Observer Pattern
```php
// When job is created, notify all matching alerts
$this->alertService->notifyNewJob($job);
```

## 🔍 Value Objects Benefits

### Type Safety
```php
// Instead of strings that can be anything:
$title = "Senior PHP Developer"; // Could be empty, null, etc.

// We use value objects:
$title = new JobTitle("Senior PHP Developer"); // Guaranteed valid
```

### Business Rules Encapsulation
```php
class Email
{
    private function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
    }
}
```

## 🧪 Testing Strategy

### Unit Tests
- **Domain Entities**: Test business rules and constraints
- **Value Objects**: Test validation and immutability
- **Domain Services**: Test business logic in isolation

### Integration Tests
- **Repository Tests**: Test data persistence
- **Controller Tests**: Test HTTP layer
- **Service Tests**: Test service orchestration

### Contract Tests
- **External Service**: Test integration with jobberwocky-extra-source
- **Email Service**: Test notification delivery

## 🚀 Performance Considerations

### Database Optimization
- **Indexes**: On frequently queried fields (title, company, location)
- **Pagination**: Efficient offset/limit implementation
- **Lazy Loading**: Doctrine lazy loading for related entities

### External Service Resilience
- **Circuit Breaker**: Prevent cascade failures
- **Timeout Handling**: Graceful degradation
- **Caching**: Reduce external API calls

### Memory Management
- **Value Objects**: Immutable, memory efficient
- **Repository Pattern**: Lazy loading of entities
- **Service Layer**: Stateless, scalable

## 🔒 Security Implementation

### Input Validation
- **Value Objects**: Built-in validation
- **Controller Validation**: Additional HTTP layer validation
- **Type Safety**: PHP 8.2+ type declarations

### Data Protection
- **SQL Injection**: Doctrine ORM parameterized queries
- **XSS Prevention**: Output escaping in email templates
- **CSRF Protection**: Ready for Symfony CSRF tokens

## 📈 Scalability Architecture

### Horizontal Scaling
- **Stateless Design**: No server-side sessions
- **Database Agnostic**: Easy to switch from SQLite to PostgreSQL/MySQL
- **Service Separation**: Independent scaling of components

### Microservices Ready
- **Clear Boundaries**: Each layer can become a service
- **API Contracts**: Well-defined interfaces
- **Event-Driven**: Ready for message queues

## 🎯 Business Logic Highlights

### Job Matching Algorithm
```php
public function matchesJob(Job $job): bool
{
    // Search pattern matching
    if ($this->searchPattern && !$job->matchesSearchCriteria($this->searchPattern)) {
        return false;
    }
    
    // Filter matching
    foreach ($this->filters as $filterKey => $filterValue) {
        if (!$job->matchesFilter($filterKey, $filterValue)) {
            return false;
        }
    }
    
    return true;
}
```

### Data Normalization
```php
private function normalizeJob(array $rawJob): array
{
    return [
        'title' => $rawJob['job_title'] ?? $rawJob['position'] ?? $rawJob['title'] ?? 'Unknown',
        'company' => $rawJob['company_name'] ?? $rawJob['employer'] ?? $rawJob['company'] ?? 'Unknown',
        'description' => $rawJob['job_description'] ?? $rawJob['description'] ?? $rawJob['details'] ?? '',
        // ... more normalization
    ];
}
```

## 🔧 Development Workflow

### Code Quality Pipeline
1. **PHPStan**: Static analysis (Level 8)
2. **PHP CS Fixer**: Code style enforcement
3. **PHPUnit**: Test coverage validation
4. **Docker**: Consistent development environment

### Git Workflow
- **Feature Branches**: Isolated development
- **Conventional Commits**: Clear commit history
- **Code Reviews**: Quality assurance
- **CI/CD Ready**: Automated testing pipeline

---

This architecture demonstrates enterprise-level PHP development practices, focusing on maintainability, testability, and scalability.

