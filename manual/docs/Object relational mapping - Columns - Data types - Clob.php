Clob (Character Large OBject) data type is meant to store data of undefined length that may be too large to store in text fields, like data that is usually stored in files.

Clob fields are meant to store only data made of printable ASCII characters whereas blob fields are meant to store all types of data.

Clob fields are usually not meant to be used as parameters of query search clause (WHERE) unless the underlying DBMS supports a feature usually known as "full text search" 

<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('clobtest', 'clob');
    }
}
</code>

