<?php

/**
 * @file
 * Contains \Drupal\drupen\RouteHandler\DefaultHandler.
 */

namespace Drupal\drupen\RouteHandler;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\drupen\ParamHandler\Manager as ParamManager;
use Drupal\drupen\ParamHandler\ParameterHandlerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DefaultHandler implements RouteHandlerInterface {

  /**
   * @var \Drupal\drupen\ParamHandler\ParameterHandlerInterface[]
   */
  protected $paramHandler;

  /**
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $generator;

  public function __construct(UrlGeneratorInterface $generator, ParamManager $manager) {
    $this->generator = $generator;
    $this->paramHandler = $manager;
  }

  public function applies(Route $route) {
    return TRUE;
  }

  public function getUrls(RouteCollection $collection) {
    $urls = [];
    foreach ($collection as $route_name => $route) {
      if (isset($route->getOptions()['parameters']) && count($route->getOptions()['parameters'])) {
        $replacements = [];
        foreach ($route->getOptions()['parameters'] as $key => $param) {
          $params = $this->getParameters($param['type']);
          if (!$params) {
            continue;
          }
          $replacements[$key] = $params;
        }
        $results = [];
        if ($replacements) {
          generatePermutations(DRUPEN_STRING_SEPERATOR, $results, ...array_values($replacements));
          $keys = array_keys($replacements);
          foreach ($results as $result) {
            $result = explode(DRUPEN_STRING_SEPERATOR, $result);
            if (count($keys) == count($result)) {
              $urls[] = renderLink($route_name, array_combine($keys, $result));
            }
          }
        }
      }
    }
    return $urls;
  }

  protected function getParameterHandler(string $type) {
    foreach ($this->paramHandler->getHandlers() as $handler) {
      if ($handler->applies($type)) {
        return $handler;
      }
    }
  }

  protected function getParameters(string $type) : array {
    $handler = $this->getParameterHandler($type);
    if ($handler) {
      return $handler->getParameters($type);
    }
    return [];
  }

}
