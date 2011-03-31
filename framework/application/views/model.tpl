/**
 * This file contains definitions of all known model entities
 */

{foreach $typeTemplates as $tpl}
{$type=$tpl->getType()}
{$mapper=$tpl->getMapper()}
/**
 * Definition of model class {$type}
 */
dojo.declare("wcmf.model.{$type}Class", wcmf.model.meta.Node, {
  name: '{$type}',
  isRootType: {if $tpl->getProperty('isRootType') == true}true{else}false{/if},
  attributes: [
{foreach $mapper->getAttributes() as $attribute}
    { name: "{$attribute->getName()}", isEditable: {if $attribute->getIsEditable()}true{else}false{/if}, tags: [{if sizeof($attribute->getTags()) > 0}"{join('","',$attribute->getTags())}"{/if}] }{if !$attribute@last},
{/if}
{/foreach}

  ],
  relations: [
{foreach $mapper->getRelations() as $relation}
    { name: "{$relation->getOtherRole()}", type: "{$relation->getOtherType()}" }{if !$relation@last},
{/if}
{/foreach}

  ],
  displayValues: [
{foreach $tpl->getDisplayValueNames() as $value}
    '{$value}'{if !$value@last},
{/if}
{/foreach}
  ]
});
// create a type instance
wcmf.model.{$type} = new wcmf.model.{$type}Class;
// register the type at the meta model
wcmf.model.meta.Model.registerType(wcmf.model.{$type});

{/foreach}