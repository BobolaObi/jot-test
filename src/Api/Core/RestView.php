<?php

/**
  * Class RestView
  * Interface describe a View for rendering an Response
  */

namespace Legacy\Jot\Api\Core;


interface RestView extends RestAction {
    /**
       * Render this view
       * Show($restServer)
       * @param RestServer $restServer
       * @return string HTML
       *
     * */
    function show(RestServer $restServer) ;
}
?>