<?php
class Doctrine_CollectionTestCase extends Doctrine_UnitTestCase {
    public function testAdd() {
        $coll = new Doctrine_Collection($this->objTable);
        $coll->add(new User());
        $this->assertEqual($coll->count(),1);
        $coll->add(new User());
        $this->assertTrue($coll->count(),2);

        $this->assertEqual($coll->getKeys(), array(0,1));

        $coll[2] = new User();

        $this->assertTrue($coll->count(),3);
        $this->assertEqual($coll->getKeys(), array(0,1,2));
    }

    public function testLoadRelatedForAssociation() {
        $coll = $this->connection->query("FROM User");

        $this->assertEqual($coll->count(), 8);

        $coll[0]->Group[1]->name = "Actors House 2";

        $coll[0]->Group[2]->name = "Actors House 3";

        $coll[2]->Group[0]->name = "Actors House 4";
        $coll[2]->Group[1]->name = "Actors House 5";
        $coll[2]->Group[2]->name = "Actors House 6";
        
        $coll[5]->Group[0]->name = "Actors House 7";
        $coll[5]->Group[1]->name = "Actors House 8";
        $coll[5]->Group[2]->name = "Actors House 9";
        
        $coll->save();
        
        $this->connection->clear();
        
        $coll = $this->connection->query("FROM User");

        $this->assertEqual($coll->count(), 8);
        $this->assertEqual($coll[0]->Group->count(), 2);
        $this->assertEqual($coll[1]->Group->count(), 1);
        $this->assertEqual($coll[2]->Group->count(), 3);
        $this->assertEqual($coll[5]->Group->count(), 3);

        $this->connection->clear();
        
        $coll = $this->connection->query("FROM User");

        $this->assertEqual($coll->count(), 8);

        $count = $this->dbh->count();

        $coll->loadRelated("Group");
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($coll[0]->Group->count(), 2);
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($coll[1]->Group->count(), 1);

        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertEqual($coll[2]->Group->count(), 3);

        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($coll[5]->Group->count(), 3);

        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->connection->clear();
    }
    public function testLoadRelated() {
        $coll = $this->connection->query("FROM User(id)");

        $q = $coll->loadRelated();

        $this->assertTrue($q instanceof Doctrine_Query);
        
        $q->addFrom('User.Group');

        $coll2 = $q->execute($coll->getPrimaryKeys());
        $this->assertEqual($coll2->count(), $coll->count());

        $count = $this->dbh->count();
        $coll[0]->Group[0];
        $this->assertEqual($count, $this->dbh->count());
    }
    public function testLoadRelatedForLocalKeyRelation() {
        $coll = $this->connection->query("FROM User");

        $this->assertEqual($coll->count(), 8);
        
        $count = $this->dbh->count();
        $coll->loadRelated("Email");

        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertEqual($coll[0]->Email->address, "zYne@example.com");

        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertEqual($coll[2]->Email->address, "caine@example.com");

        $this->assertEqual($coll[3]->Email->address, "kitano@example.com");

        $this->assertEqual($coll[4]->Email->address, "stallone@example.com");

        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->connection->clear();
    }
    public function testLoadRelatedForForeignKey() {
        $coll = $this->connection->query("FROM User");
        $this->assertEqual($coll->count(), 8);
        
        $count = $this->dbh->count();
        $coll->loadRelated("Phonenumber");

        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertEqual($coll[0]->Phonenumber[0]->phonenumber, "123 123");

        $this->assertEqual(($count + 1), $this->dbh->count());

        $coll[0]->Phonenumber[1]->phonenumber;

        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertEqual($coll[4]->Phonenumber[0]->phonenumber, "111 555 333");
        $this->assertEqual($coll[4]["Phonenumber"][1]->phonenumber, "123 213");
        $this->assertEqual($coll[4]["Phonenumber"][2]->phonenumber, "444 555");

        $this->assertEqual($coll[5]->Phonenumber[0]->phonenumber, "111 222 333");


        $this->assertEqual($coll[6]->Phonenumber[0]->phonenumber, "111 222 333");
        $this->assertEqual($coll[6]["Phonenumber"][1]->phonenumber, "222 123");
        $this->assertEqual($coll[6]["Phonenumber"][2]->phonenumber, "123 456");
        
        $this->assertEqual(($count + 1), $this->dbh->count());
        
        $this->connection->clear();
    }
    public function testCount() {
        $coll = new Doctrine_Collection($this->connection->getTable('User'));
        $this->assertEqual($coll->count(), 0);
        $coll[0];
        $this->assertEqual($coll->count(), 1);
    }
    public function testExpand() {
        $users = $this->connection->query("FROM User-b.Phonenumber-l WHERE User.Phonenumber.phonenumber LIKE '%123%'");

        $this->assertTrue($users instanceof Doctrine_Collection_Batch);
        $this->assertTrue($users[1] instanceof User);
        $this->assertTrue($users[1]->Phonenumber instanceof Doctrine_Collection_Lazy);
        $data = $users[1]->Phonenumber->getData();
        
        $coll = $users[1]->Phonenumber;

        $this->assertEqual(count($data), 1);

        foreach($coll as $record) {
            $record->phonenumber;
        }

        $coll[1];

        $this->assertEqual(count($coll), 3);

        $this->assertEqual($coll[2]->getState(), Doctrine_Record::STATE_PROXY);



        $coll->setKeyColumn('id');
        $user = $this->connection->getTable("User")->find(4);

    }
    public function testGenerator() {
        $coll = new Doctrine_Collection($this->objTable);
        $coll->setKeyColumn('name');

        $user = new User();
        $user->name = "name";
        $coll->add($user);

        $this->assertEqual($coll["name"], $user);


        $this->connection->getTable("email")->setAttribute(Doctrine::ATTR_COLL_KEY,"address");
        $emails = $this->connection->getTable("email")->findAll();
        foreach($emails as $k => $v) {
            $this->assertTrue(gettype($k), "string");
        }

    }
    public function testFetchCollectionWithIdAsIndex() {
        $user = new User();
        $user->setAttribute(Doctrine::ATTR_COLL_KEY, 'id');
        
        $users = $user->getTable()->findAll();
        $this->assertFalse($users->contains(0));
        $this->assertEqual($users->count(), 8);
        
        $this->assertEqual($users[0]->getState(), Doctrine_Record::STATE_TCLEAN); 
        $this->assertEqual($users[4]->getState(), Doctrine_Record::STATE_CLEAN);
    }
    public function testFetchCollectionWithNameAsIndex() {
        $user = new User();
        $user->setAttribute(Doctrine::ATTR_COLL_KEY, 'name');
        
        $users = $user->getTable()->findAll();
        $this->assertFalse($users->contains(0));
        $this->assertEqual($users->count(), 8);
        
        $this->assertEqual($users[0]->getState(), Doctrine_Record::STATE_TCLEAN); 
        $this->assertEqual($users['zYne']->getState(), Doctrine_Record::STATE_CLEAN);
    }
    public function testFetchMultipleCollections() {
        $this->connection->clear();
        
        $user = new User();
        $user->setAttribute(Doctrine::ATTR_COLL_KEY, 'id');
        $phonenumber = new Phonenumber();
        $phonenumber->setAttribute(Doctrine::ATTR_COLL_KEY, 'id');


        $q = new Doctrine_Query();
        $users = $q->from('User.Phonenumber')->execute();
        $this->assertFalse($users->contains(0));
        $this->assertEqual($users->count(), 8);

        $this->assertEqual($users[0]->getState(), Doctrine_Record::STATE_TCLEAN);
        $this->assertEqual($users[2]->getState(), Doctrine_Record::STATE_TCLEAN);
        $this->assertEqual($users[3]->getState(), Doctrine_Record::STATE_TCLEAN);
        $this->assertEqual($users[4]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($users[4]->name, 'zYne');

        $this->assertEqual($users[4]->Phonenumber[0]->exists(), false);
        $this->assertEqual($users[4]->Phonenumber[0]->getState(), Doctrine_Record::STATE_TDIRTY);
        $this->assertEqual($users[4]->Phonenumber[1]->exists(), false);
        $this->assertEqual($users[4]->Phonenumber[2]->getState(), Doctrine_Record::STATE_CLEAN);
    }
}
?>
