<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class JobberwockyAPITest extends TestCase
{
    private $baseUrl = 'http://localhost:8000/api.php';
    
    public function testCreateJob()
    {
        $jobData = [
            'title' => 'Test PHP Developer',
            'company' => 'TestCorp',
            'description' => 'Test job description',
            'location' => 'Test City',
            'salary' => '$5000-$7000',
            'type' => 'full-time',
            'level' => 'senior',
            'tags' => ['PHP', 'Testing'],
            'remote' => true
        ];
        
        $response = $this->makeRequest('POST', $this->baseUrl, $jobData);
        
        $this->assertEquals(201, $response['status']);
        $this->assertTrue($response['data']['success']);
        $this->assertEquals('Test PHP Developer', $response['data']['data']['title']);
        $this->assertEquals('TestCorp', $response['data']['data']['company']);
        
        return $response['data']['data']['id'];
    }
    
    public function testSearchJobs()
    {
        // First create a job
        $this->testCreateJob();
        
        // Then search for it
        $response = $this->makeRequest('GET', $this->baseUrl . '?q=PHP&limit=10');
        
        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['data']['success']);
        $this->assertIsArray($response['data']['data']);
        $this->assertGreaterThan(0, count($response['data']['data']));
    }
    
    public function testCreateJobAlert()
    {
        $alertData = [
            'email' => 'test@example.com',
            'searchPattern' => 'PHP developer',
            'filters' => [
                'type' => 'full-time',
                'level' => 'senior'
            ]
        ];
        
        $response = $this->makeRequest('POST', $this->baseUrl, $alertData);
        
        $this->assertEquals(201, $response['status']);
        $this->assertTrue($response['data']['success']);
        $this->assertEquals('test@example.com', $response['data']['data']['email']);
        $this->assertEquals('PHP developer', $response['data']['data']['searchPattern']);
    }
    
    public function testValidationErrors()
    {
        // Test missing required fields
        $invalidData = [
            'title' => 'Test Job'
            // Missing company and description
        ];
        
        $response = $this->makeRequest('POST', $this->baseUrl, $invalidData);
        
        $this->assertEquals(400, $response['status']);
        $this->assertStringContainsString('required', $response['data']['error']);
    }
    
    private function makeRequest($method, $url, $data = null)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
}

// Simple test runner
if (php_sapi_name() === 'cli') {
    echo "Running Jobberwocky API Tests...\n\n";
    
    $test = new JobberwockyAPITest();
    $tests = ['testCreateJob', 'testSearchJobs', 'testCreateJobAlert', 'testValidationErrors'];
    
    $passed = 0;
    $failed = 0;
    
    foreach ($tests as $testMethod) {
        try {
            echo "Running $testMethod... ";
            $test->$testMethod();
            echo "PASSED\n";
            $passed++;
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
    
    echo "\nResults: $passed passed, $failed failed\n";
    
    if ($failed === 0) {
        echo "All tests passed! ✅\n";
        exit(0);
    } else {
        echo "Some tests failed! ❌\n";
        exit(1);
    }
}

