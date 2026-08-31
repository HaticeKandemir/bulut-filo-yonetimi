import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { useUploadImportBatch } from './useUploadImportBatch'

const EXCEL_MIME_TYPES = new Set([
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'application/vnd.ms-excel',
])

const uploadSchema = z.object({
  file: z
    .instanceof(FileList)
    .refine((files) => files.length === 1, { message: 'required' })
    .refine((files) => EXCEL_MIME_TYPES.has(files[0]?.type ?? ''), { message: 'invalidType' })
    .transform((files) => files[0] as File),
})

type UploadFormInput = z.input<typeof uploadSchema>
type UploadFormValues = z.output<typeof uploadSchema>

interface UploadImportFormProps {
  onUploaded: (batchId: number) => void
}

export function UploadImportForm({ onUploaded }: UploadImportFormProps) {
  const { t } = useTranslation()
  const upload = useUploadImportBatch()
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<UploadFormInput, unknown, UploadFormValues>({ resolver: zodResolver(uploadSchema) })

  const onSubmit = handleSubmit((values) => {
    upload.mutate(values.file, {
      onSuccess: (response) => {
        reset()
        onUploaded(response.data.id)
      },
    })
  })

  return (
    <form onSubmit={(event) => void onSubmit(event)} className="flex flex-col gap-2 rounded border border-gray-200 p-4">
      <label htmlFor="import-file" className="text-sm font-medium text-gray-700">
        {t('imports.upload.label')}
      </label>
      <input id="import-file" type="file" accept=".xlsx,.xls" {...register('file')} className="text-sm" />
      {errors.file?.message === 'required' && <p className="text-sm text-red-600">{t('imports.upload.errors.required')}</p>}
      {errors.file?.message === 'invalidType' && <p className="text-sm text-red-600">{t('imports.upload.errors.invalidType')}</p>}
      {upload.isError && <p className="text-sm text-red-600">{t('common.error')}</p>}
      <button
        type="submit"
        disabled={upload.isPending}
        className="w-fit rounded bg-gray-900 px-4 py-1.5 text-sm text-white disabled:opacity-40"
      >
        {upload.isPending ? t('imports.upload.uploading') : t('imports.upload.submit')}
      </button>
    </form>
  )
}
