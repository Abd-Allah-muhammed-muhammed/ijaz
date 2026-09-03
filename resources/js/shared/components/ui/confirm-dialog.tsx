import { Button, Modal } from 'react-bootstrap';
import type { ConfirmDialogProps } from './types';

export default function ConfirmDialog({
  show,
  title,
  message,
  confirmLabel,
  cancelLabel,
  onConfirm,
  onCancel,
  confirmVariant = 'danger',
  loading = false,
  centered = true,
}: ConfirmDialogProps) {
  return (
    <Modal show={show} onHide={onCancel} centered={centered}>
      <Modal.Header closeButton>
        <Modal.Title>{title}</Modal.Title>
      </Modal.Header>
      <Modal.Body>{message}</Modal.Body>
      <Modal.Footer>
        <Button variant="secondary" onClick={onCancel} disabled={loading}>
          {cancelLabel}
        </Button>
        <Button
          variant={confirmVariant}
          onClick={onConfirm}
          disabled={loading}
        >
          {loading ? (
            <span className="indicator-progress" style={{ display: 'block' }}>
              <span className="spinner-border spinner-border-sm align-middle me-2" />
              {confirmLabel}
            </span>
          ) : (
            confirmLabel
          )}
        </Button>
      </Modal.Footer>
    </Modal>
  );
}
