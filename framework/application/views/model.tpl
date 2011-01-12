/**
 * This file contains definitions of all known model entities
 */
dojo.provide("wcmf.model");

/**
 * Base class for all model classes
 */
dojo.declare("wcmf.model.base.Class", null, {
});

{foreach $typeTemplates as $tpl}
{$type=$tpl->getType()}
{$mapper=$tpl->getMapper()}
/**
 * Definition of model class {$type}
 */
dojo.declare("wcmf.model.{$type}Class", wcmf.model.base.Class, {
  'type': '{$type}',
  'attributes': [
{foreach $mapper->getAttributes() as $attribute}
    { name: "{$attribute->name}", isEditable: {if $attribute->isEditable}true{else}false{/if}, tags: [{if sizeof($attribute->tags) > 0}"{join('","',$attribute->tags)}"{/if}] }{if !$attribute@last},
{/if}
{/foreach}

  ],
  'relations': [
{foreach $mapper->getRelations() as $relation}
    { name: "{$relation->otherRole}", type: "{$relation->otherType}" }{if !$relation@last},
{/if}
{/foreach}

  ]
});
wcmf.model.{$type} = new wcmf.model.{$type}Class;

{/foreach}
