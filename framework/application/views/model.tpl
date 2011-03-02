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
    { name: "{$attribute->getName()}", isEditable: {if $attribute->getIsEditable()}true{else}false{/if}, tags: [{if sizeof($attribute->getTags()) > 0}"{join('","',$attribute->getTags())}"{/if}] }{if !$attribute@last},
{/if}
{/foreach}

  ],
  'relations': [
{foreach $mapper->getRelations() as $relation}
    { name: "{$relation->getOtherRole()}", type: "{$relation->getOtherType()}" }{if !$relation@last},
{/if}
{/foreach}

  ]
});
wcmf.model.{$type} = new wcmf.model.{$type}Class;

{/foreach}
