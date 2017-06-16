<?php
namespace SlaxWeb\Cache\Database\Service;

use Pimple\Container;

/**
 * Cache Component Service Provider
 *
 * Registers the extended model loader.
 *
 * @package   SlaxWeb\Cache
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.1
 */
class Provider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register services
     *
     * Called when the container is about to register this provider. It defines
     * all the required services for the Cache component.
     *
     * @param \Pimple\Container $app Service Container
     * @return void
     */
    public function register(Container $app)
    {
        if (isset($app["dbModelLoader.service"])) {
            $app->extend(
                "dbModelLoader.service",
                function(\SlaxWeb\Database\BaseModel $model, Container $app) {
                    if ($model instanceof \SlaxWeb\Cache\Database\Model) {
                        $model->setCache($app["cache.service"]);
                    }
                    return $model;
                }
            );
        } else {
            $app["logger.service"]("System")->error(
                "The 'database' component is not installed. Unable to extend 'loadDBModel.service'."
            );
        }
    }
}
