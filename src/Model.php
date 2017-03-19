<?php
namespace SlaxWeb\Cache\Database;

use SlaxWeb\Cache\Manager as CacheManager;
use SlaxWeb\Cache\Exception\CacheDataNotFoundException;
use SlaxWeb\Database\Interfaces\Result as ResultInterface;

/**
 * Cache Database Model Extension
 *
 * The Model Extension class overrides the required methods to store the obtained
 * data into cache, and return it before the actuall call is made, and adds other
 * required methods to manipulate the cache.
 *
 * @package   SlaxWeb\Cache
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.1
 */
abstract class Model extends \SlaxWeb\Database\BaseModel
{
    /**
     * Cache Manager object
     *
     * @var \SlaxWeb\CacheManager
     */
    protected $cache = null;

    /**
     * Skip cache
     *
     * @var bool
     */
    protected $skipCache = true;

    /**
     * Set Cache
     *
     * Sets the Cache Manager to the model that is used later to obtain and store
     * data from and to cache.
     *
     * @param \SlaxWeb\Cache\Manager $cache Cache Manager object
     * @return self
     */
    public function setCache(CacheManager $cache): Model
    {
        $this->cache = $cache;
        $this->skipCache = false;
        return $this;
    }

    /**
     * Skip cache
     *
     * The next call to retrieve data will ignore cache, nor will it store the retrieved
     * data to cache.
     *
     * @return self
     */
    public function skipCache(): Model
    {
        $this->skipCache = true;
        return $this;
    }

    /**
     * @inheritDoc
     *
     * Checks if the cache contains a record for the desired query, and returns
     * the cached result object.
     */
    public function select(array $columns): ResultInterface
    {
        if ($this->primKey === "") {
            $this->logger->warning(
                "Primary key of model not set.",
                ["model" => get_class($this)]
            );
        }

        $name = "database_{$this->table}_"
            . sha1($this->qBuilder->getPredicates()->convert());

        if ($this->skipCache === false) {
            try {
                $this->result = $this->cache->read($name);
                $this->skipCache = false;
                return $this->result;
            } catch (CacheDataNotFoundException $e) {
                $this->logger->info("Data not found for query");
            }
        }

        parent::select($columns);
        if ($this->skipCache === false) {
            $this->cache->write($name, $this->result);
            $this->skipCache = false;
        }

        return $this->result;
    }
}
