<?php

namespace Drupal\Tests\metastore;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityInterface;
use Drupal\metastore\FileMapper;
use MockChain\Chain;
use MockChain\Options;
use Drupal\common\UrlHostTokenResolver;
use Drupal\metastore\DataNodeLifeCycle;
use Drupal\node\Entity\Node;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 *
 */
class DataNodeLifeCycleTest extends TestCase {

  /**
   *
   */
  public function testNotNode() {
    $this->expectExceptionMessage("We only work with nodes.");

    $entity = (new Chain($this))
      ->add(EntityInterface::class, "blah", NULL)
      ->getMock();

    new DataNodeLifeCycle($entity);
  }

  /**
   *
   */
  public function testNonDataNode() {
    $this->expectExceptionMessage("We only work with data nodes.");

    $node = (new Chain($this))
      ->add(Node::class, "bundle", "blah")
      ->getMock();

    new DataNodeLifeCycle($node);
  }

  /**
   *
   */
  public function testPresaveDistribution() {
    $container = (new Chain($this))
      ->add(Container::class, "get", RequestStack::class)
      ->add(RequestStack::class, "getCurrentRequest", Request::class)
      ->add(Request::class, "getHost", "dkan")
      ->add(Request::class, "getSchemeAndHttpHost", "http://dkan")
      ->getMock();

    \Drupal::setContainer($container);

    $metadata = (object) [
      "data" => (object) [
        "downloadURL" => "http://dkan/some/path/blah",
      ],
    ];

    $options = (new Options())
      ->add('field_json_metadata', (object) ["value" => json_encode($metadata)])
      ->add('field_data_type', (object) ["value" => "distribution"])
      ->index(0);

    $nodeChain = new Chain($this);
    $node = $nodeChain
      ->add(Node::class, "bundle", "data")
      ->add(Node::class, "get", $options)
      ->add(Node::class, "set", NULL, "metadata")
      ->getMock();


    $fileMapperChain = (new Chain($this))
      ->add(FileMapper::class, 'register', "12345", 'fileMapperRegister');
    $fileMapper = $fileMapperChain->getMock();

    // Test that the downloadUrl is being registered correctly with the
    // FileMapper.
    $lifeCycle = new DataNodeLifeCycle($node);
    $lifeCycle->setFileMapper($fileMapper);
    $lifeCycle->presave();

    $inputs = $fileMapperChain->getStoredInput("fileMapperRegister");
    $this->assertNotEmpty($inputs);

    $newdata = $nodeChain->getStoredInput('metadata');
    $newdata = json_decode($newdata[1]);

    $this->assertNotEquals($metadata->data->downloadURL, $newdata->data->downloadURL);
  }

}
