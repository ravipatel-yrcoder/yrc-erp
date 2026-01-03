<?php
class Models_ProdCategory extends TinyPHP_ActiveRecord
{
    public $tableName = "product_categories";

    public $company_id = 0;
    public $parent_id = null;
    public $name = "";
    public $code = null;
    public $description = null;
    public $status = "active";
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));
    }

    protected function doBeforeCreate() {

        $this->company_id = auth()->getCompanyId();

        $date = date("Y-m-d H:i:s");        
        $this->created_at = $date;
        $this->updated_at = $date;

        return $this->validate();
    }

    protected function doBeforeUpdate() {

        $date = date("Y-m-d H:i:s");        
        $this->updated_at = $date;

        return $this->validate();
    }

    public function validate() {

        $this->validateCategoryInfo();
        return !$this->hasErrors();
    }

    private function isUnique($name, $id=0) {
        
        $name = strtolower(trim($name));
        $companyId = auth()->getCompanyId();

        $bind = [$name, $companyId, "archived"];
        $sql = "SELECT COUNT(id) FROM product_categories
                WHERE lower(name)=? AND company_id=? AND status<>?";
        if( $id ) {
            $sql .=" AND id!=?";
            $bind[] = $id;
        }
        
        $count = self::getVar($sql, $bind);

        return !$count == 1;
    }

    public function validateCategoryInfo() {

        if(empty($this->name)) {
            $this->addError(validationErrMsg("required", "Name"), "name");
        } else {
            if( !$this->isUnique($this->name, $this->id) ) {
                $this->addError(validationErrMsg("duplicate", "Name"), "name");
            }
        }

        // Optionally, validate status
        if(!in_array($this->status, ['active','inactive', 'archived'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Status"), "status");
        }

        return !$this->hasErrors();
    }
    


    private static function buildCategoryTree(array $categories, $parentId = null): array {
        
        $tree = [];

        foreach ($categories as $category) {

            // assuming 'parent_id' holds the parent id
            if ($category->parent_id == $parentId) {
                
                // recursively build children
                $children = self::buildCategoryTree($categories, $category->id);

                if ($children) {
                    $category->children = $children;
                } else {
                    $category->children = [];
                }

                $tree[] = $category;
            }
        }

        return $tree;
    }

    
    public static function getCategories($companyId, $format='list'): array {

        global $db;

        $sql = "SELECT c.id, c.name AS category, c.code, c.parent_id, p.name AS parent_category, c.description, c.status, c.created_at FROM  product_categories c
        LEFT JOIN product_categories p ON c.parent_id = p.id
        WHERE 
        c.company_id = ? AND
        c.status <> ?
        ORDER BY  c.name";
        
        $categories = $db->fetchAll($sql, [$companyId, "archived"]);

        if( $format === "tree" ) {
            $categories = self::buildCategoryTree($categories);
        }

        return $categories;
    }
}
?>