<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.RelationDescription.php");
require_once(WCMF_BASE."wcmf/lib/model/mapper/class.RDBOneToManyRelationDescription.php");
require_once(WCMF_BASE."wcmf/lib/model/mapper/class.RDBManyToOneRelationDescription.php");
require_once(WCMF_BASE."wcmf/lib/model/mapper/class.RDBManyToManyRelationDescription.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class RelationDescriptionTest extends WCMFTestCase
{
  public function testCompare()
  {
    // to same type
    $rel1 = new RDBOneToManyRelationDescription('Page', 'ParentPage', 'Page', 'ChildPage',
            '1', '1', '0', 'unbounded', 'composite', 'none', 'true', 'true', 'child', 'id', 'fk_page_id');
    $rel2 = new RDBManyToOneRelationDescription('Page', 'ChildPage', 'Page', 'ParentPage',
            '0', 'unbounded', '1', '1', 'none', 'composite', 'true', 'true', 'parent', 'id', 'fk_page_id');
    $this->assertTrue($rel1->isSameRelation($rel2));

    // to other type
    $rel1 = new RDBOneToManyRelationDescription('Page', 'TitlePage', 'Image', 'TitleImage',
            '1', '1', '0', '1', 'composite', 'none', 'true', 'true', 'child', 'id', 'fk_titlepage_id');
    $rel2 = new RDBManyToOneRelationDescription('Image', 'TitleImage', 'Page', 'TitlePage',
            '0', '1', '1', '1', 'none', 'composite', 'true', 'true', 'parent', 'id', 'fk_titlepage_id');
    $this->assertTrue($rel1->isSameRelation($rel2));

    // many to mayn
    $rel1 = new RDBManyToManyRelationDescription(
        /* this -> nm  */ new RDBOneToManyRelationDescription('Page', 'Page', 'NMPageDocument', 'NMPageDocument',
                '1', '1', '0', 'unbounded', 'shared', 'none', 'true', 'true', 'child', 'id', 'fk_page_id'),
        /* nm -> other */ new RDBManyToOneRelationDescription('NMPageDocument', 'NMPageDocument', 'Document', 'Document',
                '0', 'unbounded', '1', '1', 'none', 'shared', 'true', 'true', 'parent', 'id', 'fk_document_id')
        );
    $rel2 = new RDBManyToManyRelationDescription(
        /* this -> nm  */ new RDBOneToManyRelationDescription('Document', 'Document', 'NMPageDocument', 'NMPageDocument',
                '1', '1', '0', 'unbounded', 'shared', 'none', 'true', 'true', 'child', 'id', 'fk_document_id'),
        /* nm -> other */ new RDBManyToOneRelationDescription('NMPageDocument', 'NMPageDocument', 'Page', 'Page',
                '0', 'unbounded', '1', '1', 'none', 'shared', 'true', 'true', 'parent', 'id', 'fk_page_id')
        );
    $this->assertTrue($rel1->isSameRelation($rel2));
  }
}
?>