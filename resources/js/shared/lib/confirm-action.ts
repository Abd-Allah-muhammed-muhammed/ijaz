import withReactContent from 'sweetalert2-react-content';
import Swal from 'sweetalert2';

/**
 * Exact SweetAlert confirm used by `ConfirmAction` / legacy Table deletes.
 * Keep this config identical — do not invent a parallel dialog shape.
 */
export function confirmWithSweetAlert(options: {
  title: string;
  cancelButtonText: string;
  confirmButtonText: string;
}): Promise<boolean> {
  const swal = withReactContent(Swal);

  return swal
    .fire({
      title: options.title,
      icon: 'warning',
      showCancelButton: true,
      cancelButtonText: options.cancelButtonText,
      confirmButtonText: options.confirmButtonText,
    })
    .then((result) => Boolean(result.isConfirmed));
}
