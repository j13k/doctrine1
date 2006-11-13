<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Transaction');
/**
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Transaction_Oracle extends Doctrine_Transaction {
    /**
     * createSavepoint
     * creates a new savepoint
     *
     * @param string $savepoint     name of a savepoint to set
     * @return void
     */
    public function createSavePoint($savepoint) {
        $query = 'SAVEPOINT '.$savepoint;
        
        return $this->conn->getDbh()->query($query);
    }
    /**
     * releaseSavePoint
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to release
     * @return void
     */
    public function releaseSavePoint($savepoint) {
        // oracle doesn't support manual releasing of savepoints
        return true;
    }
    /**
     * rollbackSavePoint
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to rollback to
     * @return void
     */
    public function rollbackSavePoint($savepoint) {
        $query = 'ROLLBACK TO SAVEPOINT '.$savepoint;
        
        return $this->conn->getDbh()->query($query);
    }
}
