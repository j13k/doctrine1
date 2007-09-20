<?php
require_once('spyc.php');

/*
 *  $Id: Yml.php 1080 2007-02-10 18:17:08Z jwage $
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
/**
 * Doctrine_Parser_Yml
 *
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 */
class Doctrine_Parser_Yml extends Doctrine_Parser
{
    /**
     * dumpData
     *
     * Dump an array of data to a specified path to yml file
     * 
     * @param string $array 
     * @param string $path 
     * @return void
     * @author Jonathan H. Wage
     */
    public function dumpData($array, $path = null)
    {
        $spyc = new Spyc();
        
        $yml = $spyc->dump($array, false, false);
        
        if ($path) {
            return file_put_contents($path, $yml);
        } else {
            return $yml;
        }
    }
    /**
     * loadData
     *
     * Load and parse data from a yml file
     * 
     * @param string $path 
     * @return void
     * @author Jonathan H. Wage
     */
    public function loadData($path)
    {
        $spyc = new Spyc();
        
        $array = $spyc->load($path);
        
        return $array;
    }
}