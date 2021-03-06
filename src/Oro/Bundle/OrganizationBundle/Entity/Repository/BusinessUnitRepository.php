<?php
namespace Oro\Bundle\OrganizationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Entity\User;

class BusinessUnitRepository extends EntityRepository
{
     /**
     * Build business units tree for user page
     *
     * @param User $user
     * @return array
     */
    public function getBusinessUnitsTree(User $user = null)
    {
        $businessUnits = $this->createQueryBuilder('businessUnit')
                    ->select(
                        array(
                            'businessUnit.id',
                            'businessUnit.name',
                            'IDENTITY(businessUnit.owner) parent',
                        )
                    );
        if ($user && $user->getId()) {
            $units = $user->getBusinessUnits()->map(
                function (BusinessUnit $businessUnit) {
                    return $businessUnit->getId();
                }
            );
            $units = $units->toArray();
            if ($units) {
                $businessUnits->addSelect('CASE WHEN businessUnit.id IN (:userUnits) THEN 1 ELSE 0 END as hasUser');
                $businessUnits->setParameter(':userUnits', $units);
            }
        }
        $businessUnits = $businessUnits->getQuery()->getArrayResult();
        $children = array();
        foreach ($businessUnits as &$businessUnit) {
            $parent = $businessUnit['parent'] ?: 0;
            $children[$parent][] = &$businessUnit;
        }
        unset($businessUnit);
        foreach ($businessUnits as &$businessUnit) {
            if (isset($children[$businessUnit['id']])) {
                $businessUnit['children'] = $children[$businessUnit['id']];
            }
        }
        unset($businessUnit);
        if (isset($children[0])) {
            $children = $children[0];
        }

        return $children;
    }

    /**
     * @param array $businessUnits
     * @return mixed
     */
    public function getBusinessUnits(array $businessUnits)
    {
        return $this->createQueryBuilder('businessUnit')
            ->select('businessUnit')
            ->where('businessUnit.id in (:ids)')
            ->setParameter('ids', $businessUnits)
            ->getQuery()
            ->execute();
    }
}
