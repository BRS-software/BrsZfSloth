<?php
namespace BrsZfSloth\Definition;

interface DefinitionAwareInterface
{
    public function getDefinition();
    public function setDefinition(Definition $definition);
    // public function getDefinitionName();
}