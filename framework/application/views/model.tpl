/**
 * This file contains definitions of all known model entities
 */
dojo.provide("wcmf.model");
{foreach $nodeTemplates as $node}
{$type=$node->getType()}
/**
 * Definition of model class {$type}
 */
dojo.declare("wcmf.model.{$type}Class", null, {
  'type': '{$type}',
  'attributes': [
{foreach $node->getValueNames() as $value}
    { name: "{$value}" }{if !$value@last},
{/if}
{/foreach}

  ]
});
wcmf.model.{$type} = new wcmf.model.{$type}Class;

{/foreach}
