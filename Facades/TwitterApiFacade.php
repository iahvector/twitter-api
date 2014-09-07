<?php namespace Iahvector\TwitterApi\Facades;
 
use Illuminate\Support\Facades\Facade;
 
class TwitterApiFacade extends Facade {
 
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor()
  {
  	return 'twitter-api';
  }
 
}