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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::ORM::Internal;

/**
 * A CommitOrderNode is a temporary wrapper around ClassMetadata objects
 * that is used to sort the order of commits.
 * 
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class Doctrine_ORM_Internal_CommitOrderNode
{
    const NOT_VISITED = 1;
    const IN_PROGRESS = 2;
    const VISITED = 3;
    
    private $_traversalState;
    private $_predecessor;
    private $_status;
    private $_calculator;
    private $_relatedNodes = array();
    
    private $_discoveryTime;
    private $_finishingTime;
    
    private $_wrappedObj;
    private $_relationEdges = array();
    
    
    public function __construct($wrappedObj, Doctrine_ORM_Internal_CommitOrderCalculator $calc)
    {
        $this->_wrappedObj = $wrappedObj;
        $this->_calculator = $calc;
    }
    
    public function getClass()
    {
        return $this->_wrappedObj;
    }
    
    public function setPredecessor($node)
    {
        $this->_predecessor = $node;
    }
    
    public function getPredecessor()
    {
        return $this->_predecessor;
    }
    
    public function markNotVisited()
    {
        $this->_traversalState = self::NOT_VISITED;
    }
    
    public function markInProgress()
    {
        $this->_traversalState = self::IN_PROGRESS;
    }
    
    public function markVisited()
    {
        $this->_traversalState = self::VISITED;
    }
    
    public function isNotVisited()
    {
        return $this->_traversalState == self::NOT_VISITED;
    }
    
    public function isInProgress()
    {
        return $this->_traversalState == self::IN_PROGRESS;
    }
    
    public function visit()
    {
        $this->markInProgress();
        $this->setDiscoveryTime($this->_calculator->getNextTime());
        
        foreach ($this->getRelatedNodes() as $node) {
            if ($node->isNotVisited()) {
                $node->setPredecessor($this);
                $node->visit();
            }
            if ($node->isInProgress()) {
                // back edge => cycle
                //TODO: anything to do here?
            }
        }
        
        $this->markVisited();
        $this->_calculator->prependNode($this);
        $this->setFinishingTime($this->_calculator->getNextTime());
    }
    
    public function setDiscoveryTime($time)
    {
        $this->_discoveryTime = $time;
    }
    
    public function setFinishingTime($time)
    {
        $this->_finishingTime = $time;
    }
    
    public function getDiscoveryTime()
    {
        return $this->_discoveryTime;
    }
    
    public function getFinishingTime()
    {
        return $this->_finishingTime;
    }
    
    public function getRelatedNodes()
    {
        return $this->_relatedNodes;
    }
    
    /**
     * Adds a directed dependency (an edge on the graph). "$this -before-> $other".
     *
     * @param Doctrine_Internal_CommitOrderNode $node
     */
    public function before(Doctrine_ORM_Internal_CommitOrderNode $node)
    {
        $this->_relatedNodes[] = $node;
    }
}

?>