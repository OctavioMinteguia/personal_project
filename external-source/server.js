const express = require('express');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors());
app.use(express.json());

// Simulate messy external API response format
const mockJobs = [
  {
    // Different field names and inconsistent structure
    job_title: "Senior PHP Developer",
    company_name: "TechCorp Argentina",
    job_description: "We are looking for a senior PHP developer with extensive experience in Symfony framework. Must have strong knowledge of MySQL, Redis, and modern PHP practices.",
    city: "Buenos Aires",
    compensation: "$80000 - $120000 ARS",
    employment_type: "fulltime",
    experience_level: "senior",
    skills: ["PHP", "Symfony", "MySQL", "Redis", "Docker"],
    work_from_home: true,
    posted_date: "2024-01-15T10:30:00Z"
  },
  {
    // Another inconsistent format
    title: "Frontend React Developer",
    employer: "StartupXYZ",
    summary: "Join our fast-growing startup as a React developer. Work with modern technologies and help build amazing user experiences.",
    location: "Remote",
    salary: "USD 4000-6000",
    type: "contract",
    seniority: "mid",
    technologies: ["React", "TypeScript", "Node.js", "GraphQL"],
    remote: "yes",
    created_at: "2024-01-14T15:45:00Z"
  },
  {
    // Yet another format variation
    position: "DevOps Engineer",
    company: "CloudTech Solutions",
    details: "We need a DevOps engineer to help us scale our infrastructure. Experience with AWS, Kubernetes, and CI/CD pipelines required.",
    address: "Córdoba, Argentina",
    pay: "$60000 - $90000 ARS",
    job_type: "permanent",
    level: "senior",
    tags: "AWS,Kubernetes,Docker,CI/CD,Python",
    telecommute: false,
    date_posted: "2024-01-13T09:20:00Z"
  },
  {
    // More variations
    role: "Python Backend Developer",
    company_name: "DataFlow Inc",
    job_description: "Backend developer position working with Python, Django, and PostgreSQL. Experience with data processing and APIs essential.",
    city: "Rosario",
    compensation: "$50000 - $75000 ARS",
    employment_type: "full-time",
    experience_level: "intermediate",
    skills: ["Python", "Django", "PostgreSQL", "Redis", "Celery"],
    work_from_home: false,
    posted_date: "2024-01-12T14:15:00Z"
  },
  {
    title: "Mobile App Developer",
    employer: "AppMakers",
    summary: "Mobile app development using React Native. Experience with iOS and Android platforms required.",
    location: "Buenos Aires",
    salary: "USD 3500-5000",
    type: "internship",
    seniority: "junior",
    technologies: ["React Native", "JavaScript", "iOS", "Android"],
    remote: true,
    created_at: "2024-01-11T11:30:00Z"
  }
];

app.get('/api/jobs', (req, res) => {
  try {
    // Simulate some randomness in response
    const shuffledJobs = [...mockJobs].sort(() => Math.random() - 0.5);
    
    // Sometimes return partial data or different structure
    const response = {
      status: "success",
      data: shuffledJobs,
      timestamp: new Date().toISOString(),
      version: "2.0.0"
    };

    res.json(response);
  } catch (error) {
    res.status(500).json({
      status: "error",
      message: "Internal server error",
      error: error.message
    });
  }
});

app.get('/health', (req, res) => {
  res.json({
    status: "healthy",
    timestamp: new Date().toISOString(),
    uptime: process.uptime()
  });
});

app.listen(PORT, () => {
  console.log(`Jobberwocky Extra Source server running on port ${PORT}`);
  console.log(`Health check: http://localhost:${PORT}/health`);
  console.log(`Jobs API: http://localhost:${PORT}/api/jobs`);
});


