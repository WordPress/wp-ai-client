<?php
/**
 * Tests for WordPress\AI_Client\Cache\WordPress_Cache
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Tests\Cache;

use DateInterval;
use WordPress\AI_Client\Cache\WordPress_Cache;
use WordPress\AI_Client\PHPUnit\Includes\Test_Case;

class WordPress_Cache_Tests extends Test_Case {

	/**
	 * The cache instance.
	 *
	 * @var WordPress_Cache
	 */
	private $cache;

	public function set_up(): void {
		parent::set_up();
		$this->cache = new WordPress_Cache();
	}

	/**
	 * Tests that get returns the cached value.
	 */
	public function test_get_returns_cached_value(): void {
		wp_cache_set( 'test_key', 'test_value', 'wp_ai_client' );

		$this->assertSame( 'test_value', $this->cache->get( 'test_key' ) );
	}

	/**
	 * Tests that get returns default value for missing key.
	 */
	public function test_get_returns_default_for_missing_key(): void {
		$this->assertNull( $this->cache->get( 'nonexistent_key' ) );
		$this->assertSame( 'default', $this->cache->get( 'nonexistent_key', 'default' ) );
	}

	/**
	 * Tests that set stores a value in the cache.
	 */
	public function test_set_stores_value(): void {
		$result = $this->cache->set( 'test_key', 'test_value' );

		$this->assertTrue( $result );
		$this->assertSame( 'test_value', wp_cache_get( 'test_key', 'wp_ai_client' ) );
	}

	/**
	 * Tests that set handles different value types.
	 */
	public function test_set_handles_different_types(): void {
		$this->cache->set( 'string_key', 'string_value' );
		$this->cache->set( 'int_key', 42 );
		$this->cache->set( 'array_key', array( 'foo' => 'bar' ) );
		$this->cache->set( 'bool_key', true );

		$this->assertSame( 'string_value', $this->cache->get( 'string_key' ) );
		$this->assertSame( 42, $this->cache->get( 'int_key' ) );
		$this->assertSame( array( 'foo' => 'bar' ), $this->cache->get( 'array_key' ) );
		$this->assertTrue( $this->cache->get( 'bool_key' ) );
	}

	/**
	 * Tests that set accepts DateInterval TTL.
	 */
	public function test_set_accepts_date_interval_ttl(): void {
		$ttl    = new DateInterval( 'PT1H' ); // 1 hour.
		$result = $this->cache->set( 'test_key', 'test_value', $ttl );

		$this->assertTrue( $result );
		$this->assertSame( 'test_value', $this->cache->get( 'test_key' ) );
	}

	/**
	 * Tests that set accepts integer TTL.
	 */
	public function test_set_accepts_integer_ttl(): void {
		$result = $this->cache->set( 'test_key', 'test_value', 3600 );

		$this->assertTrue( $result );
		$this->assertSame( 'test_value', $this->cache->get( 'test_key' ) );
	}

	/**
	 * Tests that delete removes an item from the cache.
	 */
	public function test_delete_removes_item(): void {
		$this->cache->set( 'test_key', 'test_value' );

		$result = $this->cache->delete( 'test_key' );

		$this->assertTrue( $result );
		$this->assertNull( $this->cache->get( 'test_key' ) );
	}

	/**
	 * Tests that delete returns false for nonexistent key.
	 */
	public function test_delete_nonexistent_key(): void {
		$result = $this->cache->delete( 'nonexistent_key' );

		// WordPress wp_cache_delete returns false for nonexistent keys.
		$this->assertFalse( $result );
	}

	/**
	 * Tests that has returns true for existing key.
	 */
	public function test_has_returns_true_for_existing_key(): void {
		$this->cache->set( 'test_key', 'test_value' );

		$this->assertTrue( $this->cache->has( 'test_key' ) );
	}

	/**
	 * Tests that has returns false for missing key.
	 */
	public function test_has_returns_false_for_missing_key(): void {
		$this->assertFalse( $this->cache->has( 'nonexistent_key' ) );
	}

	/**
	 * Tests that has returns true for falsy cached values.
	 */
	public function test_has_returns_true_for_falsy_values(): void {
		$this->cache->set( 'null_key', null );
		$this->cache->set( 'false_key', false );
		$this->cache->set( 'zero_key', 0 );
		$this->cache->set( 'empty_string_key', '' );

		$this->assertTrue( $this->cache->has( 'null_key' ) );
		$this->assertTrue( $this->cache->has( 'false_key' ) );
		$this->assertTrue( $this->cache->has( 'zero_key' ) );
		$this->assertTrue( $this->cache->has( 'empty_string_key' ) );
	}

	/**
	 * Tests that getMultiple returns values for multiple keys.
	 */
	public function test_get_multiple_returns_values(): void {
		$this->cache->set( 'key1', 'value1' );
		$this->cache->set( 'key2', 'value2' );
		$this->cache->set( 'key3', 'value3' );

		$result = $this->cache->getMultiple( array( 'key1', 'key2', 'key3' ) );

		$this->assertSame(
			array(
				'key1' => 'value1',
				'key2' => 'value2',
				'key3' => 'value3',
			),
			$result
		);
	}

	/**
	 * Tests that getMultiple returns default for missing keys.
	 */
	public function test_get_multiple_returns_default_for_missing(): void {
		$this->cache->set( 'key1', 'value1' );

		$result = $this->cache->getMultiple( array( 'key1', 'missing_key' ), 'default' );

		$this->assertSame(
			array(
				'key1'        => 'value1',
				'missing_key' => 'default',
			),
			$result
		);
	}

	/**
	 * Tests that setMultiple stores multiple values.
	 */
	public function test_set_multiple_stores_values(): void {
		$result = $this->cache->setMultiple(
			array(
				'key1' => 'value1',
				'key2' => 'value2',
				'key3' => 'value3',
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( 'value1', $this->cache->get( 'key1' ) );
		$this->assertSame( 'value2', $this->cache->get( 'key2' ) );
		$this->assertSame( 'value3', $this->cache->get( 'key3' ) );
	}

	/**
	 * Tests that deleteMultiple removes multiple items.
	 */
	public function test_delete_multiple_removes_items(): void {
		$this->cache->setMultiple(
			array(
				'key1' => 'value1',
				'key2' => 'value2',
				'key3' => 'value3',
			)
		);

		$result = $this->cache->deleteMultiple( array( 'key1', 'key2' ) );

		$this->assertTrue( $result );
		$this->assertFalse( $this->cache->has( 'key1' ) );
		$this->assertFalse( $this->cache->has( 'key2' ) );
		$this->assertTrue( $this->cache->has( 'key3' ) );
	}

	/**
	 * Tests that clear clears the cache group when group flushing is supported.
	 */
	public function test_clear_clears_group_with_flush_group_support(): void {
		if ( ! function_exists( 'wp_cache_supports' ) || ! wp_cache_supports( 'flush_group' ) ) {
			$this->markTestSkipped( 'Test requires cache with group flush support.' );
		}

		$this->cache->set( 'key1', 'value1' );
		$this->cache->set( 'key2', 'value2' );

		$result = $this->cache->clear();

		$this->assertTrue( $result );
		$this->assertFalse( $this->cache->has( 'key1' ) );
		$this->assertFalse( $this->cache->has( 'key2' ) );
	}

	/**
	 * Tests that clear returns false when group flushing is not supported.
	 */
	public function test_clear_returns_false_without_flush_group_support(): void {
		// The default WordPress object cache does not support group flushing.
		// wp_cache_supports('flush_group') returns false by default.
		if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) ) {
			$this->markTestSkipped( 'Test requires cache without group flush support.' );
		}

		$this->cache->set( 'test_key', 'test_value' );

		$result = $this->cache->clear();

		$this->assertFalse( $result );
	}
}
