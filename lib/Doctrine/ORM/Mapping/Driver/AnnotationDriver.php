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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Exceptions\MappingException;

/* Addendum annotation reflection extensions */
if ( ! class_exists('\Addendum', false)) {
    require __DIR__ . '/addendum/annotations.php';
}
require __DIR__ . '/DoctrineAnnotations.php';

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @author robo
 */
class AnnotationDriver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $annotClass = new \Addendum\ReflectionAnnotatedClass($className);

        // Evaluate DoctrineEntity annotation
        if (($entityAnnot = $annotClass->getAnnotation('DoctrineEntity')) === false) {
            throw new MappingException("$className is no entity.");
        }
        $metadata->setCustomRepositoryClass($entityAnnot->repositoryClass);

        // Evaluate DoctrineTable annotation
        if ($tableAnnot = $annotClass->getAnnotation('DoctrineTable')) {
            $metadata->setPrimaryTable(array(
                'name' => $tableAnnot->name,
                'schema' => $tableAnnot->schema,
                'catalog' => $tableAnnot->catalog
            ));
        }

        // Evaluate DoctrineInheritanceType annotation
        if ($inheritanceTypeAnnot = $annotClass->getAnnotation('DoctrineInheritanceType')) {
            $metadata->setInheritanceType($inheritanceTypeAnnot->value);
        }

        // Evaluate DoctrineDiscriminatorColumn annotation
        if ($discrColumnAnnot = $annotClass->getAnnotation('DoctrineDiscriminatorColumn')) {
            $metadata->setDiscriminatorColumn(array(
                'name' => $discrColumnAnnot->name,
                'type' => $discrColumnAnnot->type,
                'length' => $discrColumnAnnot->length
            ));
        }

        // Evaluate DoctrineDiscriminatorMap annotation
        if ($discrValueAnnot = $annotClass->getAnnotation('DoctrineDiscriminatorMap')) {
            $metadata->setDiscriminatorMap((array)$discrValueAnnot->value);
        }

        // Evaluate DoctrineSubClasses annotation
        if ($subClassesAnnot = $annotClass->getAnnotation('DoctrineSubClasses')) {
            $metadata->setSubclasses($subClassesAnnot->value);
        }

        // Evaluate annotations on properties/fields
        foreach ($annotClass->getProperties() as $property) {
            $mapping = array();
            $mapping['fieldName'] = $property->getName();

            // Check for DoctrineJoinColummn/DoctrineJoinColumns annotations
            $joinColumns = array();
            if ($joinColumnAnnot = $property->getAnnotation('DoctrineJoinColumn')) {
                $joinColumns[] = array(
                        'name' => $joinColumnAnnot->name,
                        'referencedColumnName' => $joinColumnAnnot->referencedColumnName,
                        'unique' => $joinColumnAnnot->unique,
                        'nullable' => $joinColumnAnnot->nullable,
                        'onDelete' => $joinColumnAnnot->onDelete,
                        'onUpdate' => $joinColumnAnnot->onUpdate
                );
            } else if ($joinColumnsAnnot = $property->getAnnotation('DoctrineJoinColumns')) {
                $joinColumns = $joinColumnsAnnot->value;
            }

            // Field can only be annotated with one of: DoctrineColumn,
            // DoctrineOneToOne, DoctrineOneToMany, DoctrineManyToOne, DoctrineManyToMany
            if ($columnAnnot = $property->getAnnotation('DoctrineColumn')) {
                if ($columnAnnot->type == null) {
                    throw new MappingException("Missing type on property " . $property->getName());
                }
                $mapping['type'] = $columnAnnot->type;
                $mapping['length'] = $columnAnnot->length;
                $mapping['nullable'] = $columnAnnot->nullable;
                if ($idAnnot = $property->getAnnotation('DoctrineId')) {
                    $mapping['id'] = true;
                }
                if ($idGeneratorAnnot = $property->getAnnotation('DoctrineIdGenerator')) {
                    $mapping['idGenerator'] = $idGeneratorAnnot->value;
                }
                $metadata->mapField($mapping);
            } else if ($oneToOneAnnot = $property->getAnnotation('DoctrineOneToOne')) {
                $mapping['targetEntity'] = $oneToOneAnnot->targetEntity;
                $mapping['joinColumns'] = $joinColumns;
                $mapping['mappedBy'] = $oneToOneAnnot->mappedBy;
                $mapping['cascade'] = $oneToOneAnnot->cascade;
                $metadata->mapOneToOne($mapping);
            } else if ($oneToManyAnnot = $property->getAnnotation('DoctrineOneToMany')) {
                $mapping['mappedBy'] = $oneToManyAnnot->mappedBy;
                $mapping['targetEntity'] = $oneToManyAnnot->targetEntity;
                $mapping['cascade'] = $oneToManyAnnot->cascade;
                $metadata->mapOneToMany($mapping);
            } else if ($manyToOneAnnot = $property->getAnnotation('DoctrineManyToOne')) {
                $mapping['joinColumns'] = $joinColumns;
                $mapping['targetEntity'] = $manyToOneAnnot->targetEntity;
                $metadata->mapManyToOne($mapping);
            } else if ($manyToManyAnnot = $property->getAnnotation('DoctrineManyToMany')) {
                $joinTable = array();
                if ($joinTableAnnot = $property->getAnnotation('DoctrineJoinTable')) {
                    $joinTable = array(
                        'name' => $joinTableAnnot->name,
                        'schema' => $joinTableAnnot->schema,
                        'catalog' => $joinTableAnnot->catalog,
                        'joinColumns' => $joinTableAnnot->joinColumns,
                        'inverseJoinColumns' => $joinTableAnnot->inverseJoinColumns
                    );
                }
                $mapping['joinTable'] = $joinTable;
                $mapping['targetEntity'] = $manyToManyAnnot->targetEntity;
                $mapping['mappedBy'] = $manyToManyAnnot->mappedBy;
                $metadata->mapManyToMany($mapping);
            }
            
        }
    }
}
