import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import type { InstitutionNode, Vehicle } from '../../types/api'
import { flattenInstitutions } from '../../utils/institutions'
import { useUpdateVehicle } from './useUpdateVehicle'

const editSchema = z.object({
  brand: z.string().trim().min(1, { message: 'required' }),
  model: z.string().trim().min(1, { message: 'required' }),
  institutionId: z
    .string()
    .min(1, { message: 'required' })
    .transform((value) => Number(value)),
  plate: z.string().trim().min(1, { message: 'required' }),
  status: z.enum(['active', 'passive', 'left_fleet']),
})

type EditFormInput = z.input<typeof editSchema>
type EditFormValues = z.output<typeof editSchema>

interface VehicleEditFormProps {
  vehicle: Vehicle
  institutions: InstitutionNode[]
  onCancel: () => void
  onSaved: () => void
}

const controlClassName =
  'mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500'

export function VehicleEditForm({ vehicle, institutions, onCancel, onSaved }: VehicleEditFormProps) {
  const { t } = useTranslation()
  const update = useUpdateVehicle(vehicle.id)
  const flatInstitutions = flattenInstitutions(institutions)
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<EditFormInput, unknown, EditFormValues>({
    resolver: zodResolver(editSchema),
    defaultValues: {
      brand: vehicle.brand,
      model: vehicle.model,
      institutionId: String(vehicle.institution.id),
      plate: vehicle.active_plate?.plate ?? '',
      status: vehicle.status,
    },
  })

  const onSubmit = handleSubmit((values) => {
    update.mutate(
      {
        brand: values.brand,
        model: values.model,
        institution_id: values.institutionId,
        plate: values.plate,
        status: values.status,
      },
      { onSuccess: onSaved },
    )
  })

  const mutationError = update.error
  const conflictingVin = mutationError !== null && mutationError.status === 409 ? mutationError.body?.conflicting_vehicle_vin : null
  const generalError = mutationError !== null && mutationError.status !== 409 ? (mutationError.body?.message ?? t('common.error')) : null

  return (
    <form onSubmit={(event) => void onSubmit(event)} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label htmlFor="edit-brand" className="text-sm font-medium text-gray-700">
          {t('vehicles.columns.brand')}
        </label>
        <input id="edit-brand" type="text" {...register('brand')} className={controlClassName} />
        {errors.brand && <p className="mt-1 text-sm text-red-600">{t('vehicles.detail.edit.errors.required')}</p>}
      </div>

      <div>
        <label htmlFor="edit-model" className="text-sm font-medium text-gray-700">
          {t('vehicles.columns.model')}
        </label>
        <input id="edit-model" type="text" {...register('model')} className={controlClassName} />
        {errors.model && <p className="mt-1 text-sm text-red-600">{t('vehicles.detail.edit.errors.required')}</p>}
      </div>

      <div>
        <label htmlFor="edit-plate" className="text-sm font-medium text-gray-700">
          {t('vehicles.columns.plate')}
        </label>
        <input id="edit-plate" type="text" {...register('plate')} className={controlClassName} />
        {errors.plate && <p className="mt-1 text-sm text-red-600">{t('vehicles.detail.edit.errors.required')}</p>}
      </div>

      <div>
        <label htmlFor="edit-institution" className="text-sm font-medium text-gray-700">
          {t('vehicles.columns.institution')}
        </label>
        <select id="edit-institution" {...register('institutionId')} className={controlClassName}>
          {flatInstitutions.map((institution) => (
            <option key={institution.id} value={String(institution.id)}>
              {institution.label}
            </option>
          ))}
        </select>
        {errors.institutionId && <p className="mt-1 text-sm text-red-600">{t('vehicles.detail.edit.errors.required')}</p>}
      </div>

      <div>
        <label htmlFor="edit-status" className="text-sm font-medium text-gray-700">
          {t('vehicles.columns.status')}
        </label>
        <select id="edit-status" {...register('status')} className={controlClassName}>
          <option value="active">{t('vehicles.status.active')}</option>
          <option value="passive">{t('vehicles.status.passive')}</option>
          <option value="left_fleet">{t('vehicles.status.left_fleet')}</option>
        </select>
      </div>

      {conflictingVin !== null && conflictingVin !== undefined && (
        <p className="text-sm text-red-600 sm:col-span-2">{t('vehicles.detail.edit.errors.plateConflict', { vin: conflictingVin })}</p>
      )}
      {generalError !== null && <p className="text-sm text-red-600 sm:col-span-2">{generalError}</p>}

      <div className="flex gap-2 sm:col-span-2">
        <button
          type="submit"
          disabled={update.isPending}
          className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
          {update.isPending ? t('vehicles.detail.edit.saving') : t('vehicles.detail.edit.save')}
        </button>
        <button
          type="button"
          onClick={onCancel}
          className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
        >
          {t('vehicles.detail.edit.cancel')}
        </button>
      </div>
    </form>
  )
}
