import { describe, expect, it } from 'vitest'
import type { InstitutionNode } from '../types/api'
import { flattenInstitutions } from './institutions'

describe('flattenInstitutions', () => {
  it('flattens a nested tree, indenting each level with an em dash', () => {
    const tree: InstitutionNode[] = [
      {
        id: 1,
        name: 'PTT',
        code: 'PTT',
        children: [
          {
            id: 2,
            name: 'PTT E-AVM',
            code: 'PTT-EAVM',
            children: [{ id: 3, name: 'PTTEM', code: 'PTTEM', children: [] }],
          },
        ],
      },
    ]

    expect(flattenInstitutions(tree)).toEqual([
      { id: 1, label: 'PTT' },
      { id: 2, label: '— PTT E-AVM' },
      { id: 3, label: '—— PTTEM' },
    ])
  })

  it('returns an empty array for an empty tree', () => {
    expect(flattenInstitutions([])).toEqual([])
  })
})
