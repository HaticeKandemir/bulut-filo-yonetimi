import type { InstitutionNode } from '../types/api'

export interface FlatInstitution {
  id: number
  label: string
}

export function flattenInstitutions(nodes: InstitutionNode[], depth = 0): FlatInstitution[] {
  return nodes.flatMap((node) => [
    { id: node.id, label: `${'—'.repeat(depth)} ${node.name}`.trim() },
    ...flattenInstitutions(node.children, depth + 1),
  ])
}
