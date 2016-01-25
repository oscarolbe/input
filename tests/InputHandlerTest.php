<?php
declare(strict_types=1);

namespace Linio\Component\Input;

use Linio\Component\Input\Instantiator\InstantiatorInterface;
use Prophecy\Argument;

class TestUser
{
    protected $name;
    protected $age;
    protected $date;
    public $isActive;
    protected $related;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function setAge($age)
    {
        $this->age = $age;
    }

    public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;
    }

    public function getRelated() : TestUser
    {
        return $this->related;
    }

    public function setRelated(TestUser $related)
    {
        $this->related = $related;
    }
}

class TestInputHandler extends InputHandler
{
    public function define()
    {
        $this->add('title', 'string');
        $this->add('size', 'int');
        $this->add('dimensions', 'int[]');
        $this->add('date', 'datetime');
        $this->add('metadata', 'array');

        $simple = $this->add('simple', 'array');
        $simple->add('title', 'string', ['default' => 'Barfoo']);
        $simple->add('size', 'int', ['required' => false, 'default' => 15]);
        $simple->add('date', 'datetime');

        $author = $this->add('author', 'Linio\Component\Input\TestUser');
        $author->add('name', 'string');
        $author->add('age', 'int');
        $author->add('is_active', 'bool', ['required' => false]);
        $related = $author->add('related', 'Linio\Component\Input\TestUser');
        $related->add('name', 'string');
        $related->add('age', 'int');

        $fans = $this->add('fans', 'Linio\Component\Input\TestUser[]');
        $fans->add('name', 'string');
        $fans->add('age', 'int');
    }
}

class InputHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testIsHandlingBasicInput()
    {
        $input = [
            'title' => 'Foobar',
            'size' => 35,
            'dimensions' => [11, 22, 33],
            'date' => '2015-01-01 22:50',
            'metadata' => [
                'foo' => 'bar',
            ],
            'simple' => [
                'date' => '2015-01-01 22:50',
            ],
            'author' => [
                'name' => 'Barfoo',
                'age' => 28,
                'related' => [
                    'name' => 'Barfoo',
                    'age' => 28,
                ],
            ],
            'fans' => [
                [
                    'name' => 'A',
                    'age' => 18,
                ],
                [
                    'name' => 'B',
                    'age' => 28,
                ],
                [
                    'name' => 'C',
                    'age' => 38,
                ]
            ],
        ];

        $inputHandler = new TestInputHandler();
        $inputHandler->bind($input);
        $this->assertTrue($inputHandler->isValid());

        // Basic fields
        $this->assertEquals('Foobar', $inputHandler->getData('title'));
        $this->assertEquals(35, $inputHandler->getData('size'));

        // Scalar collection
        $this->assertEquals([11, 22, 33], $inputHandler->getData('dimensions'));

        // Transformer
        $this->assertEquals(new \DateTime('2015-01-01 22:50'), $inputHandler->getData('date'));

        // Mixed array
        $this->assertEquals(['foo' => 'bar'], $inputHandler->getData('metadata'));

        // Typed array
        $this->assertEquals(['title' => 'Barfoo', 'size' => 15, 'date' => new \DateTime('2015-01-01 22:50')], $inputHandler->getData('simple'));

        // Object and nested object
        $related = new TestUser();
        $related->setName('Barfoo');
        $related->setAge(28);
        $author = new TestUser();
        $author->setName('Barfoo');
        $author->setAge(28);
        $author->setRelated($related);
        $this->assertEquals($author, $inputHandler->getData('author'));

        // Object collection
        $fanA = new TestUser();
        $fanA->setName('A');
        $fanA->setAge(18);
        $fanB = new TestUser();
        $fanB->setName('B');
        $fanB->setAge(28);
        $fanC = new TestUser();
        $fanC->setName('C');
        $fanC->setAge(38);
        $this->assertEquals([$fanA, $fanB, $fanC], $inputHandler->getData('fans'));
    }

    public function testIsHandlingErrors()
    {
        $input = [
            'size' => '35',
            'dimensions' => ['11', 22, 33],
            'date' => '2015-01-01 22:50',
            'metadata' => [
                'foo' => 'bar',
            ],
            'simple' => [
                'date' => '2015-01-01 22:50',
            ],
            'author' => [
                'name' => 'Barfoo',
                'age' => 28,
                'related' => [
                    'name' => 'Barfoo',
                    'age' => 28,
                ],
            ],
            'fans' => [
                [
                    'name' => 'A',
                    'age' => 18,
                ],
                [
                    'name' => 'B',
                    'age' => 28,
                ],
                [
                    'name' => 'C',
                    'age' => 38,
                ]
            ],
        ];

        $inputHandler = new TestInputHandler();
        $inputHandler->bind($input);
        $this->assertFalse($inputHandler->isValid());
        $this->assertEquals([
            'Missing required field: title',
        ], $inputHandler->getErrors());
        $this->assertEquals('Missing required field: title', $inputHandler->getErrorsAsString());
    }

    public function testIsHandlingTypeJuggling()
    {
        $input = [
            'title' => '',
            'size' => 0,
            'dimensions' => [0, 0, 0],
            'date' => '2015-01-01 22:50',
            'metadata' => [
                'foo' => 'bar',
            ],
            'simple' => [
                'date' => '2015-01-01 22:50',
            ],
            'author' => [
                'name' => 'Barfoo',
                'age' => 28,
                'is_active' => false,
                'related' => [
                    'name' => 'Barfoo',
                    'age' => 28,
                ],
            ],
            'fans' => [
                [
                    'name' => 'A',
                    'age' => 18,
                ],
                [
                    'name' => 'B',
                    'age' => 28,
                ],
                [
                    'name' => 'C',
                    'age' => 38,
                ]
            ],
        ];

        $inputHandler = new TestInputHandler();
        $inputHandler->bind($input);
        $this->assertTrue($inputHandler->isValid());

        $this->assertEquals('', $inputHandler->getData('title'));
        $this->assertEquals(0, $inputHandler->getData('size'));
        $this->assertEquals([0, 0, 0], $inputHandler->getData('dimensions'));
        $this->assertEquals(false, $inputHandler->getData('author')->isActive);
    }

    public function testIsHandlingInputValidationWithInstantiator()
    {
        $input = [
            'title' => 'Foobar',
            'size' => 35,
            'dimensions' => [11, 22, 33],
            'date' => '2015-01-01 22:50',
            'metadata' => [
                'foo' => 'bar',
            ],
            'simple' => [
                'date' => '2015-01-01 22:50',
            ],
            'user' => [
                'name' => false,
                'age' => '28',
            ],
            'author' => [
                'name' => 'Barfoo',
                'age' => 28,
                'related' => [
                    'name' => 'Barfoo',
                    'age' => 28,
                ],
            ],
            'fans' => [
                [
                    'name' => 'A',
                    'age' => 18,
                ],
                [
                    'name' => 'B',
                    'age' => 28,
                ],
                [
                    'name' => 'C',
                    'age' => 38,
                ]
            ],
        ];

        $instantiator = $this->prophesize(InstantiatorInterface::class);
        $instantiator->instantiate('Linio\Component\Input\TestUser', [])->shouldNotBeCalled();

        $inputHandler = new TestInputHandler();
        $user = $inputHandler->add('user', 'Linio\Component\Input\TestUser', ['instantiator' => $instantiator->reveal()]);
        $user->add('name', 'string');
        $user->add('age', 'int');
        $inputHandler->bind($input);
        $this->assertFalse($inputHandler->isValid());
        $this->assertEquals([
            '[name] Value does not match type: string',
        ], $inputHandler->getErrors());
    }
}