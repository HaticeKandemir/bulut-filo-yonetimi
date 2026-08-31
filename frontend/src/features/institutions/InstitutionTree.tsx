import { Link } from 'react-router'
import type { InstitutionNode } from '../../types/api'

interface InstitutionTreeProps {
  nodes: InstitutionNode[]
  depth?: number
}

export function InstitutionTree({ nodes, depth = 0 }: InstitutionTreeProps) {
  return (
    <ul className={depth === 0 ? '' : 'ml-3 border-l border-gray-200 pl-4 sm:ml-5'}>
      {nodes.map((node) => (
        <li key={node.id} className="py-2">
          <div className="flex flex-wrap items-baseline gap-x-2">
            <Link
              to={`/vehicles?${new URLSearchParams({ 'filter[institution_id]': String(node.id) }).toString()}`}
              className="font-medium text-gray-900 hover:text-blue-600 hover:underline"
            >
              {node.name}
            </Link>
            <span className="text-xs text-gray-400">{node.code}</span>
          </div>
          {node.children.length > 0 && <InstitutionTree nodes={node.children} depth={depth + 1} />}
        </li>
      ))}
    </ul>
  )
}
