---
title: Testing Repositories
menuTitle: Testing Repositories
description: Unlike traditional static method calls, repositories may be mocked. This provides a great advantage over traditional static methods and grants you the same testability you would have if you were using dependency injection. When testing, you may often want to mock a call to a Restify repository in one of your controllers. For example, consider the following controller action
category: Testing
position: 15
---

```php
class ExampleTest extends TestCase
{
    public function testBasicTest()
    {
        UserRepository::partialMock()
            ->shouldReceive('index')
            ->andReturn(['data' => [],]);

        $this->withHeader('Accept', 'application/json')
            ->get('/api/restify/users')
            ->assertJsonStructure([
                'response' => 'data',
            ])->assertOk();
    }
}
```

So you can use the `partialMock` to get the partial mock instance of the repository, and then perform actions or expectations over it.
