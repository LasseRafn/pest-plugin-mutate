<?php

 declare( strict_types=1 );

 namespace Pest\Mutate\Contracts;

 use Pest\Mutate\MutationSuite;
 use Pest\Mutate\MutationTest;

 /**
  * @internal
  *
  * @final
  */
 interface Logger
 {
     /**
      * @param string $outputPath
      * @param array<string, string|float|int|null>  $pluginSettings
      */
     public function __construct( string $outputPath, array $pluginSettings );

     public function pushTestedMutation( MutationTest $mutationTest ): void;

     public function pushUntestedMutation( MutationTest $mutationTest ): void;

     public function pushTimedOutMutation( MutationTest $mutationTest ): void;

     public function pushUncoveredMutation( MutationTest $mutationTest ): void;

     public function mutationSuiteFinished( MutationSuite $mutationSuite ): void;

     public function output():void;
 }
