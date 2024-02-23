<?php
 
namespace tests\unitTest\testWillExpireAt;
 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Mockery;
 
class testWillExpireAt extends TestCase
{
  public function testWillExpireAt()
    {
        // Mock the Carbon class
        $carbonMock = $this->getMockBuilder('Carbon\Carbon')
                           ->setMethods(['parse', 'diffInHours', 'addMinutes', 'addHours', 'subHours', 'format'])
                           ->getMock();

        // Set up the expected calls and return values for the mock
        $carbonMock->expects($this->at(0))
                   ->method('parse')
                   ->willReturn($carbonMock);

        $carbonMock->expects($this->at(1))
                   ->method('parse')
                   ->willReturn($carbonMock);

        $carbonMock->expects($this->once())
                   ->method('diffInHours')
                   ->willReturnOnConsecutiveCalls(26, 36, 60, 100); // Adjust the return values based on your test cases

        $carbonMock->expects($this->exactly(4))
                   ->method('format')
                   ->willReturnOnConsecutiveCalls(
                       '2024-02-23 12:00:00', // When $difference <= 90
                       '2024-02-22 11:30:00', // When $difference <= 24
                       '2024-02-23 02:00:00', // When $difference > 24 && $difference <= 72
                       '2024-02-20 12:00:00'  // When $difference > 72
                   );

        // Mock Carbon usage within the function
        Carbon::method('parse')->willReturn($carbonMock);

        // Call the function with mocked Carbon
        $result1 = TeHelper::willExpireAt('2024-02-23 12:00:00', '2024-02-22 10:00:00');
        $result2 = TeHelper::willExpireAt('2024-02-23 12:00:00', '2024-02-23 09:30:00');
        $result3 = TeHelper::willExpireAt('2024-02-23 12:00:00', '2024-02-22 14:00:00');
        $result4 = TeHelper::willExpireAt('2024-02-23 12:00:00', '2024-02-20 12:00:00');

        // Assert the results
        $this->assertEquals('2024-02-23 12:00:00', $result1);
        $this->assertEquals('2024-02-22 11:30:00', $result2);
        $this->assertEquals('2024-02-23 02:00:00', $result3);
        $this->assertEquals('2024-02-20 12:00:00', $result4);
    }

    public function testCreateOrUpdate()
    {
        // Mocking Carbon::now() and Carbon::parse()
        Carbon::setTestNow(Carbon::parse('2022-01-01 00:00:00'));

        // Mocking necessary dependencies
        $userMock = Mockery::mock(User::class);
        $typeMock = Mockery::mock(Type::class);
        $companyMock = Mockery::mock(Company::class);
        $departmentMock = Mockery::mock(Department::class);
        $userMetaMock = Mockery::mock(UserMeta::class);
        $usersBlacklistMock = Mockery::mock(UsersBlacklist::class);
        $userLanguagesMock = Mockery::mock(UserLanguages::class);
        $townMock = Mockery::mock(Town::class);

        // Mocking methods on User model
        $userMock->shouldReceive('findOrFail')->andReturnSelf();
        $userMock->shouldReceive('detachAllRoles');
        $userMock->shouldReceive('save');
        $userMock->shouldReceive('attachRole');
        $userMock->shouldReceive('enable')->once();
        $userMock->shouldReceive('disable')->once();
        $userMock->shouldReceive('status')->andReturn('0');
        $userMock->shouldReceive('id')->andReturn(1);

        // Mocking methods on Type model
        $typeMock->shouldReceive('where')->andReturnSelf();
        $typeMock->shouldReceive('first')->andReturn((object)['id' => 1]);

        // Mocking methods on Company model
        $companyMock->shouldReceive('create')->andReturn((object)['id' => 1]);

        // Mocking methods on Department model
        $departmentMock->shouldReceive('create')->andReturn((object)['id' => 1]);

        // Mocking methods on UserMeta model
        $userMetaMock->shouldReceive('firstOrCreate')->andReturnSelf();
        $userMetaMock->shouldReceive('toArray')->andReturn([]);
        $userMetaMock->shouldReceive('save');

        // Mocking methods on UsersBlacklist model
        $usersBlacklistMock->shouldReceive('where')->andReturnSelf();
        $usersBlacklistMock->shouldReceive('get')->andReturn([]);
        $usersBlacklistMock->shouldReceive('pluck')->andReturn([]);

        // Mocking methods on UserLanguages model
        $userLanguagesMock->shouldReceive('langExist')->andReturn(0);
        $userLanguagesMock->shouldReceive('deleteLang');

        // Mocking methods on Town model
        $townMock->shouldReceive('save')->andReturnSelf();
        $townMock->shouldReceive('id')->andReturn(1);

        // Test case for CUSTOMER_ROLE_ID
        $customerRequest = [
            'role' => env('CUSTOMER_ROLE_ID'),
            'consumer_type' => 'paid',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe123',
            'post_code' => '12345',
            'address' => '123 Main St',
            'city' => 'Cityville',
            'town' => 'Towntown',
            'country' => 'Countryland',
            'reference' => 'yes',
            'additional_info' => 'Some additional info',
            'cost_place' => 'Costly Place',
            'fee' => '500',
            'time_to_charge' => '2 hours',
            'time_to_pay' => '1 week',
            'charge_ob' => 'Charge OB',
            'customer_id' => 'CUST123',
            'charge_km' => '5',
            'maximum_km' => '100',
            'new_towns' => 'New Town', // Example: Add a new town
            'user_towns_projects' => [1, 2, 3], // Example: Add user towns projects
            'status' => '1', // Example: Enable the user
            'translator_ex' => [4, 5], // Example: Add translator exceptions
        ];

        $resultCustomer = UserRepository::createOrUpdate(null, $customerRequest);

        $this->assertInstanceOf(User::class, $resultCustomer);
        $this->assertSame('paid', $resultCustomer->user_meta->consumer_type);
        $this->assertSame('yes', $resultCustomer->user_meta->reference);
        // Add more assertions for CUSTOMER_ROLE_ID specific scenarios

        // Test case for TRANSLATOR_ROLE_ID
        $translatorRequest = [
            'role' => env('TRANSLATOR_ROLE_ID'),
            'translator_type' => 'Certified',
            'worked_for' => 'yes',
            'organization_number' => 'ORG123',
            'gender' => 'Male',
            'translator_level' => 'Advanced',
            'additional_info' => 'Some additional info for translator',
            'post_code' => '54321',
            'address' => '456 Second St',
            'address_2' => 'Apt 789',
            'town' => 'Translationtown',
            'user_language' => [6, 7, 8], // Example: Add user languages
            'new_towns' => 'New Translator Town', // Example: Add a new town for translator
            'user_towns_projects' => [4, 5, 6], // Example: Add translator towns projects
            'status' => '1', // Example: Enable the translator
        ];

        $resultTranslator = UserRepository::createOrUpdate(null, $translatorRequest);

        $this->assertInstanceOf(User::class, $resultTranslator);
        $this->assertInstanceOf(User::class, $$resultCustomer);
    }

}