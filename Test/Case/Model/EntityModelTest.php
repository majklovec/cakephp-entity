<?php
App::uses('EntityModel', 'Entity.Model');
App::uses('Entity', 'Entity.Model');

/**
 *	Models
 *
 *  Author --many--> Post --one---> PostImage
 *                        --many--> PostComment
 *                        --many--> PostStar (not EntityModel)
 *
 */


class TestEntityModel extends EntityModel {

	public $useTable = false;

	public function analyzeMethodName($method) {
		return $this->_analyzeMethodName($method);
	}

}

class Author extends TestEntityModel {

	public $name = 'Author';

	public $hasOne = array(
		'Image' => array(
			'className' => 'PostImage',
		),
	);

	public $hasMany = array(
		'Post',
		'Comment' => array(
			'className' => 'PostComment',
		),
	);

}

class Post extends TestEntityModel {

	public $name = 'Post';

	public $belongsTo = array(
		'Author',
	);

	public $hasOne = array(
		'Image' => array(
			'className' => 'PostImage',
		),
	);

	public $hasMany = array(
		'Comment' => array(
			'className' => 'PostComment',
		),
		'Star' => array(
			'className' => 'PostStar',
		),
	);

	/**
	 *	dummy implementation of find().
	 */
	public function find($type = 'first', $query = array()) {
		$result = null;

		$this->beforeFind($query);

		switch ($type) {
			case 'all':
				if (isset($query['result'])) {
					$result = $query['result'];
				} else {
					$result = SampleData::$arrayOfData;
				}
				break;

			case 'first':
				if (!empty($query['contain'])) {
					$result = SampleData::$associatedData;
				} else {
					$result = SampleData::$simpleData;
				}
				break;

			case 'count':
				$result = 3;
				break;
		}

		$result = $this->afterFind($result, true);

		return $result;
	}

}

class PostImage extends TestEntityModel {

	public $name = 'PostImage';

	public $belongsTo = array(
		'Post',
	);

}

class PostComment extends TestEntityModel {

	public $name = 'PostComment';

	public $belongsTo = array(
		'Post',
	);

}

class PostStar extends AppModel {

	public $name = 'PostStar';

	public $useTable = false;

	public $belongsTo = array(
		'Post',
	);

}

/**
 *	Entities
 */

class AuthorEntity extends Entity {

}

class PostEntity extends Entity {

	// allows access of 'func2'.
	static public $allows = array('func2');

	public function allows() {
		return self::$allows;
	}

	/**
	 *	Function with public property is ok to access.
	 */
	public $func1;
	public function func1() {
		return 'result1';
	}

	/**
	 *	Function listed in allows() is ok to access.
	 */
	public function func2() {
		return 'result2';
	}

	/**
	 *	Function without public property, and not isAllowed().
	 */
	public function func3() {
		return 'result3';
	}

	/**
	 *	Protected function can not be accessed.
	 */
	public $func4;
	protected function func4() {
		return 'result4';
	}

}

class PostCommentEntity extends Entity {

}

/**
 *	Data
 */

class SampleData {

	public static $simpleData = array(
		'Post' => array(
			'id' => 123,
			'title' => 'Hello',
		),
	);

	public static $associatedData = array(
		'Post' => array(
			'id' => 123,
			'title' => 'Hello',
			'author_id' => 345,
		),
		'Author' => array(
			'id' => 345,
			'name' => 'Bob',
		),
		'Image' => array(
			'id' => 234,
			'post_id' => 123,
			'path' => '/path/to/image.jpg',
		),
		'Comment' => array(
			array(
				'id' => 101,
				'post_id' => 123,
				'comment' => 'hello',
			),
			array(
				'id' => 102,
				'post_id' => 123,
				'comment' => 'world',
			),
			array(
				'id' => 103,
				'post_id' => 123,
				'comment' => 'again',
			),
		),
		'Star' => array(
			array(
				'id' => 201,
				'post_id' => 123,
				'point' => 1,
			),
			array(
				'id' => 202,
				'post_id' => 123,
				'point' => 3,
			),
		),
	);

	public static $emptyHasManyData = array(
		'Post' => array(
			'id' => 123,
			'title' => 'Hello',
			'author_id' => 345,
		),
		'Comment' => array(
		),
	);

	public static $arrayOfData = array(
		array(
			'Post' => array(
				'id' => 123,
				'title' => 'Hello',
			),
		),
		array(
			'Post' => array(
				'id' => 124,
				'title' => 'world',
			),
		),
		array(
			'Post' => array(
				'id' => 125,
				'title' => 'again',
			),
		),
	);

}

/**
 *	Testcases
 */

class EntityModelTestCase extends CakeTestCase {

	public function startTest($method) {
		$this->Post = ClassRegistry::init('Post');
		$this->Author = ClassRegistry::init('Author');
	}

	public function endTest($method) {
		unset($this->Post);
		unset($this->Author);
		ClassRegistry::flush();
	}

	public function testEntityCreation() {

		// 1. create entity. It must be instance of PostEntity.
		$s1 = $this->Post->entity();
		$this->assertTrue(is_a($s1, 'PostEntity'));

		// 2. create entity with data. Properties can be accessed.
		$s2 = $this->Post->entity(SampleData::$simpleData);
		$this->assertTrue(is_a($s2, 'PostEntity'));
		$this->assertEqual($s2->id, 123);
		$this->assertEqual($s2->title, 'Hello');

		// 3. create entity with complex data.
		$s3 = $this->Post->entity(SampleData::$associatedData);

		// 3a. ensure object is PostEntity.
		$this->assertTrue(is_a($s3, 'PostEntity'));
		$this->assertEqual($s3->id, 123);
		$this->assertEqual($s3->title, 'Hello');

		// 3b. belongsTo association.
		$this->assertTrue(is_a($s3->author, 'AuthorEntity'));
		$this->assertEqual($s3->author->id, 345);
		$this->assertEqual($s3->author->name, 'Bob');

		// 3c. hasOne association. Entity has no specific class.
		$this->assertTrue(is_a($s3->image, 'AppEntity'));
		$this->assertEqual($s3->image->id, 234);

		// 3d. hasMany association.
		$this->assertEqual(count($s3->comments), 3);
		$this->assertTrue(is_a($s3->comments[0], 'PostCommentEntity'));
		$this->assertEqual($s3->comments[0]->comment, 'hello');

		// 3e. hasMany association (not EntityModel).
		$this->assertEqual(count($s3->star), 2);
		$this->assertTrue(is_array($s3->star[0]));
		$this->assertEqual($s3->star[0]['point'], 1);
	}

	// 4. create entity with complex data.
	public function testCreateEntityWithEmptyHasMany() {
		$s = $this->Post->entity(SampleData::$emptyHasManyData);

		$this->assertTrue(isset($s->comments));
		$this->assertTrue(is_array($s->comments));
		$this->assertEqual(count($s->comments), 0);
	}

	public function testFind() {

		// 1. Test emulated find()
		$result = $this->Post->find('first');
		$this->assertTrue(is_array($result));
		$this->assertEqual($result['Post']['title'], 'Hello');

		// 2. OK, let's roll.
		$s1 = $this->Post->find('first', array('entity' => true));
		$this->assertTrue(is_a($s1, 'PostEntity'));

		// 3. find all.
		$result = $this->Post->find('all', array('entity' => true));
		$this->assertTrue(is_array($result));
		$this->assertEqual(count($result), 3);
		$this->assertTrue(is_a($result[0], 'PostEntity'));
		$this->assertEqual($result[2]->title, 'again');

		// 4. find all with empty result.
		$result = $this->Post->find('all', array(
			'entity' => true,
			'result' => array(),
		));
		$this->assertTrue(is_array($result));
		$this->assertEqual(count($result), 0);
	}

	public function testFetchEntities() {
		// 1. entities is shortcut for
		// find('all') with entitiy => true.

		$result = $this->Post->entities();
		$this->assertTrue(is_array($result));
		$this->assertEqual(count($result), 3);
		$this->assertTrue(is_a($result[0], 'PostEntity'));
		$this->assertEqual($result[2]->title, 'again');

		// 2. allEntities is alias for entities.

		$result = $this->Post->allEntities();
		$this->assertTrue(is_array($result));
		$this->assertEqual(count($result), 3);
		$this->assertTrue(is_a($result[0], 'PostEntity'));
		$this->assertEqual($result[2]->title, 'again');
	}

	public function testMagicFetch() {
		// 1. entitesByName
		$result = $this->Post->analyzeMethodName('entitiesByName');
		$this->assertTrue($result[0]);
		$this->assertEqual($result[1], 'findAllByName');

		// 2. allEntitesByName
		$result = $this->Post->analyzeMethodName('allEntitiesByName');
		$this->assertTrue($result[0]);
		$this->assertEqual($result[1], 'findAllByName');

		// 3. entityByName
		$result = $this->Post->analyzeMethodName('entityByName');
		$this->assertTrue($result[0]);
		$this->assertEqual($result[1], 'findByName');

		// 4. findByName
		$result = $this->Post->analyzeMethodName('findByName');
		$this->assertFalse($result[0]);
		$this->assertEqual($result[1], 'findByName');

	}

	public function testEntityArrayAccess() {
		$s = $this->Post->entity();

		// 1. Simple array access.
		$s->name = 'Hello';
		$this->assertTrue(isset($s->name));
		$this->assertTrue(isset($s['name']));
		$this->assertEqual($s['name'], 'Hello');

		// 2. Non-exist property is not exists.
		$this->assertFalse(isset($s->foobar));
		$this->assertFalse(isset($s['foobar']));

		// 3. Set by array access.
		$s['title'] = 'World';
		$this->assertEqual($s['title'], 'World');
		$this->assertEqual($s->title, 'World');

		// 4. Unset by array access.
		unset($s['title']);
		$this->assertFalse(isset($s['title']));
		$this->assertFalse(isset($s->title));

		// 5. property start with '_' cannot by access;
		$s->_foo = 'Bar';
		$this->assertFalse(isset($s['_foor']));

		// 6. function can be also accessable.
		$this->assertEqual($s['func1'], 'result1');
		$this->assertEqual($s['func2'], 'result2');
		$this->assertEqual($s['func3'], null);
		$this->assertEqual($s['func4'], null);

		// 7. func1 has a public property, so it must be cached.
		$this->func1 = null; // clear cache.
		$this->assertEqual($s['func1'], 'result1');
		$this->assertEqual($s->func1, 'result1');
	}

	/**
	 *	Entity'property access is controlled by allows().
	 *	This is used by isAllowed($methodName).
	 *	Entity class can override allows() to control access.
	 */
	public function testAllowPropertyAccess() {
		$s = $this->Post->entity();

		/**
		 * This entity's allows() will return PostEntity::$allows,
		 * so changing PostEntity::$allows changes the authorization
		 * on the fly.
		 *
		 * This is not common. Don't use this on production.
		 */

		/**
		 * func1 is allowed by public property, so always ok.
		 * func4 is protected method, so always not ok.
		 */

		// 1. allow everything
		PostEntity::$allows = '*';

		$this->assertTrue($s->isAllowed('func1'));	// func1 is ok
		$this->assertTrue($s->isAllowed('func2'));	// func2 is ok
		$this->assertTrue($s->isAllowed('func3'));	// func3 is ok
		$this->assertFalse($s->isAllowed('func4'));	// func4 is protected

		// 2. allow only func3
		PostEntity::$allows = array('func3');

		$this->assertTrue($s->isAllowed('func1'));	// func1 is ok
		$this->assertFalse($s->isAllowed('func2'));	// func2 is not ok
		$this->assertTrue($s->isAllowed('func3'));	// func3 is ok
		$this->assertFalse($s->isAllowed('func4'));	// func4 is protected

		// 3. allow both func1 and func3
		PostEntity::$allows = array('func2', 'func3');

		$this->assertTrue($s->isAllowed('func1'));	// func1 is ok
		$this->assertTrue($s->isAllowed('func2'));	// func2 is ok
		$this->assertTrue($s->isAllowed('func3'));	// func3 is ok
		$this->assertFalse($s->isAllowed('func4'));	// func4 is protected

		// 4. allow nothing
		PostEntity::$allows = false;

		$this->assertTrue($s->isAllowed('func1'));	// func1 is ok
		$this->assertFalse($s->isAllowed('func2'));	// func2 is not ok
		$this->assertFalse($s->isAllowed('func3'));	// func3 is not ok
		$this->assertFalse($s->isAllowed('func4'));	// func4 is protected

		// 5. protected method can not allow by allows().
		PostEntity::$allows = array('func4');

		$this->assertFalse($s->isAllowed('func4'));	// func4 is protected
	}

	/**
	 *	For convenience at debug time, entity is converted into html
	 *	when it used as string.
	 */
	public function testStringReplesentationOfEntity() {
		$a = $this->Author->entity();

		// 1. empty entity
		$expected = '<div class="Author"></div>';
		$this->assertEqual(strval($a), $expected);

		// 2. assign entity
		$a->name = 'Basuke';

		$expected = '<div class="Author"><strong class="key">name</strong><span clas="value">Basuke</span></div>';
		$this->assertEqual(strval($a), $expected);

		// 3. value must be html escaped
		$a->job = "<b>Programmer</b>";

		$expected = '<div class="Author"><strong class="key">name</strong><span clas="value">Basuke</span><strong class="key">job</strong><span clas="value">&lt;b&gt;Programmer&lt;/b&gt;</span></div>';
		$this->assertEqual(strval($a), $expected);

	}

	/**
	 * Entity can be converted to model-aware data array.
	 */
	public function testEntityToArray() {

		// 1. simple data
		$data = array(
			'Author' => array(
				'name' => 'Basuke',
				'programmer' => 'Programmer',
			),
		);

		$author = $this->Author->entity($data);
		$reversed = $author->toArray();
		$this->assertEqual($reversed, $data);

		// 2. has many
		$data = array(
			'Author' => array(
				'id' => '123',
				'name' => 'Basuke',
				'programmer' => 'Programmer',
			),
			'Comment' => array(
				array(
					'id' => '1',
					'author_id' => '123',
					'comment' => 'Hello',
				),
				array(
					'id' => '2',
					'author_id' => '123',
					'comment' => 'World',
				),
			),
		);

		$author = $this->Author->entity($data);
		$reversed = $author->toArray();
		$this->assertEqual($reversed, $data);

		// 3. has one
		$data = array(
			'Author' => array(
				'id' => '123',
				'name' => 'Basuke',
				'programmer' => 'Programmer',
			),
			'Image' => array(
				'id' => '1',
				'author_id' => '123',
				'url' => 'Hello',
			),
		);

		$author = $this->Author->entity($data);
		$reversed = $author->toArray();
		$this->assertEqual($reversed, $data);
	}

	public function testEntityGetModel() {

		// Entity may return its Model object.
		$s1 = $this->Post->entity();
		$Model = $s1->getModel();
		$this->assertTrue(is_a($Model, 'Post'));
	}

}
