<?php
require_once('bootstrap.php');

Doctrine::generateYamlFromModels('schema.yml', 'models');