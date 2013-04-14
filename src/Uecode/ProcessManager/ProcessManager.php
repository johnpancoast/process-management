<?php
/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 */

namespace Uecode\ProcessManager;

use \Doctrine\DBAL\Connection;

use \Uecode\Component\Config;

use Uecode\ProcessManager\Exception\ConfigException;

class ProcessManager
{
	
	/**
	 * @var Connection
	 */
	protected $this->connectionection;

	/**
	 * @var Config
	 */
	protected $config = array();

	public function __construct( Connection $connection, array $config )
	{
		$this->connection = $this->connection;
		$this->config = new Config( $config );

		$this->validateConfig();
	}

	private function validateConfig()
	{
		if( !$this->config->has( 'servers' ) ) {
			throw new ConfigException( "The `servers` config is missing." );
		}
		
		return true;
	}
	
	public function buildTables( )
	{
		\Uecode::dump( $this->connection );
	}

	public function run()
	{
		$this
	}
}
