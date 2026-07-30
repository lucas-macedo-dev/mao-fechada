import { useTranslation } from "react-i18next";
import {
  useCategories,
  useCreateTransaction,
  useDeleteTransaction,
  useTransactions,
  useUpdateTransaction,
} from "../hooks/api";
import { useTutorial } from "../context/TutorialContext";
import { TutorialHint } from "../components/tutorial/TutorialHint";
import { getCategoryIconClass } from "../constants/categoryIcons";
import { extractApiError } from "../services/api";
import { useMemo, useState, type SyntheticEvent } from "react";
import {
  Title,
  Text,
  Select,
  TextInput,
  NumberInput,
  Switch,
  Button,
  ActionIcon,
  Alert,
  Stack,
  Group,
  Box,
  SimpleGrid,
  Paper,
  LoadingOverlay
} from "@mantine/core";
import { PageContainer } from "../components/ui/PageContainer";
import { SectionCard } from "../components/ui/SectionCard";
import { ActionBar } from "../components/ui/ActionBar";
import { FormModal } from "../components/FormModal";
import { ConfirmDialog } from "../components/ConfirmDialog";
import type { Transaction } from "../types/api";

const paymentMethods = [
  "credit_card",
  "debit_card",
  "cash",
  "pix",
  "bank_slip",
  "bank_transfer",
] as const;

type FormModalState =
  | null
  | { mode: "create" }
  | { mode: "edit"; item: Transaction };
type ConfirmState = null | {
  title: string;
  message: string;
  onConfirm: () => void;
};

function formatTransactionDate(value: string): string {
  const datePart = value.slice(0, 10);

  if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
    const [year, month, day] = datePart.split("-").map(Number);
    return new Date(year, month - 1, day).toLocaleDateString();
  }

  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString();
}

export function TransactionsPage() {
  const { t } = useTranslation();
  const { completeStep } = useTutorial();
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7));
  const [type, setType] = useState("");
  const [page, setPage] = useState(1);
  const [showInstallmentsOnly, setShowInstallmentsOnly] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  // Form modal state
  const [formModal, setFormModal] = useState<FormModalState>(null);
  const [formType, setFormType] = useState<"income" | "expense">("expense");
  const [formParentCategoryId, setFormParentCategoryId] = useState("");
  const [formSubcategoryId, setFormSubcategoryId] = useState("");
  const [formPaymentMethod, setFormPaymentMethod] = useState("pix");
  const [formAmount, setFormAmount] = useState<number | string>("");
  const [formTransactedAt, setFormTransactedAt] = useState(
    new Date().toISOString().slice(0, 10),
  );
  const [formNotes, setFormNotes] = useState("");

  // Installment fields (create only)
  const [installmentEnabled, setInstallmentEnabled] = useState(false);
  const [installmentCurrent, setInstallmentCurrent] = useState<number | string>(
    1,
  );
  const [installmentTotal, setInstallmentTotal] = useState<number | string>(2);

  // Confirm dialog state
  const [confirmState, setConfirmState] = useState<ConfirmState>(null);

  const createTransaction = useCreateTransaction();
  const updateTransaction = useUpdateTransaction();
  const deleteTransaction = useDeleteTransaction();
  const { data: categories = [] } = useCategories();

  const params = {
    month,
    type: type || undefined,
    per_page: 20,
    page,
    installment: showInstallmentsOnly ? true : undefined,
  };

  const { data: transactionsResponse, isLoading } = useTransactions(params);
  const transactions = transactionsResponse?.data || [];
  const meta = transactionsResponse?.meta;

  const parentFormCategories = useMemo(
    () =>
      categories.filter(
        (cat) => cat.type === formType && !cat.parent_id,
      ),
    [categories, formType],
  );

  const subcategoryOptions = useMemo(
    () =>
      categories.filter(
        (cat) =>
          formParentCategoryId &&
          String(cat.parent_id) === formParentCategoryId,
      ),
    [categories, formParentCategoryId],
  );

  const effectiveCategoryId = formSubcategoryId || formParentCategoryId;

  const selectedFormCategory = useMemo(
    () => categories.find((cat) => String(cat.id) === effectiveCategoryId),
    [categories, effectiveCategoryId],
  );

  const handleOpenCreate = () => {
    setFormType("expense");
    setFormParentCategoryId("");
    setFormSubcategoryId("");
    setFormPaymentMethod("pix");
    setFormAmount("");
    setFormTransactedAt(new Date().toISOString().slice(0, 10));
    setFormNotes("");
    setInstallmentEnabled(false);
    setInstallmentCurrent(1);
    setInstallmentTotal(2);
    setError("");
    setFormModal({ mode: "create" });
  };

  const openEditModal = (tx: Transaction) => {
    setFormType(tx.type);
    if (tx.category?.parent_id) {
      setFormParentCategoryId(String(tx.category.parent_id));
      setFormSubcategoryId(String(tx.category_id));
    } else {
      setFormParentCategoryId(String(tx.category_id));
      setFormSubcategoryId("");
    }
    setFormPaymentMethod(tx.payment_method);
    setFormAmount(Number(tx.amount));
    setFormTransactedAt(tx.transacted_at.slice(0, 10));
    setFormNotes(tx.notes || "");
    setError("");
    setFormModal({ mode: "edit", item: tx });
  };

  const handleStartEdit = (tx: Transaction) => {
    if (tx.installment_group_id) {
      const total = tx.installment_total ?? "?";
      setConfirmState({
        title: t("transactions.installment_edit_confirm_title"),
        message: t("transactions.installment_edit_confirm", { total }),
        onConfirm: () => {
          setConfirmState(null);
          openEditModal(tx);
        },
      });
      return;
    }
    openEditModal(tx);
  };

  const handleCloseModal = () => {
    setFormModal(null);
    setError("");
  };

  const handleFormSubmit = async (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!formModal) return;
    setError("");
    setSuccess("");

    if (!effectiveCategoryId) {
      setError(t("transactions.select_category"));
      return;
    }

    try {
      if (formModal.mode === "create") {
        const isInstallment =
          installmentEnabled &&
          formPaymentMethod === "credit_card" &&
          formType === "expense";

        await createTransaction.mutateAsync({
          category_id: Number(effectiveCategoryId),
          type: formType,
          payment_method: formPaymentMethod,
          amount: Number(formAmount),
          transacted_at: formTransactedAt,
          notes: formNotes || undefined,
          ...(isInstallment && {
            installment_number: Number(installmentCurrent),
            installment_total: Number(installmentTotal),
          }),
        });

        completeStep("record-transaction");
        setSuccess(t("transactions.create_success"));
        setPage(1);
      } else {
        await updateTransaction.mutateAsync({
          id: formModal.item.id,
          payload: {
            category_id: Number(effectiveCategoryId),
            type: formType,
            payment_method: formPaymentMethod,
            amount: Number(formAmount),
            transacted_at: formTransactedAt,
            notes: formNotes || undefined,
          },
        });
        setSuccess(t("transactions.update_success"));
      }
      setFormModal(null);
    } catch (err) {
      setError(extractApiError(err).message);
    }
  };

  const executeDelete = async (id: number) => {
    setError("");
    setSuccess("");
    try {
      await deleteTransaction.mutateAsync(id);
      if (formModal?.mode === "edit" && formModal.item.id === id) {
        setFormModal(null);
      }
      setSuccess(t("transactions.delete_success"));
    } catch (err) {
      setError(extractApiError(err).message);
    } finally {
      setConfirmState(null);
    }
  };

  const handleDelete = (
    id: number,
    installmentGroupId?: string | null,
    installmentTotalCount?: number | null,
  ) => {
    const message = installmentGroupId
      ? t("transactions.installment_delete_confirm", {
          total: installmentTotalCount ?? "?",
        })
      : t("transactions.delete_confirm");

    setConfirmState({
      title: t("transactions.delete_confirm_title"),
      message,
      onConfirm: () => executeDelete(id),
    });
  };

  if (isLoading) {
    return (
      <PageContainer>
        <LoadingOverlay
          visible={isLoading}
          overlayProps={{ radius: "sm", blur: 2 }}
        />
      </PageContainer>
    );
  }

  const isSubmitting =
    formModal?.mode === "create"
      ? createTransaction.isPending
      : updateTransaction.isPending;

  return (
    <PageContainer>
      <Group justify="space-between" align="center" mb="lg">
        <Title order={1}>{t("transactions.title")}</Title>
        <TutorialHint stepId="record-transaction">
          <Button
            onClick={handleOpenCreate}
            leftSection={<i className="fa-solid fa-plus" aria-hidden="true" />}
          >
            {t("transactions.new_entry")}
          </Button>
        </TutorialHint>
      </Group>

      <SectionCard mb="lg">
        <SimpleGrid cols={{ base: 1, sm: 2 }} mb="sm">
          <TextInput
            label={t("transactions.period")}
            type="month"
            value={month}
            onChange={(e) => {
              setMonth(e.target.value);
              setPage(1);
            }}
          />

          <Select
            label={t("transactions.type")}
            value={type}
            onChange={(val) => {
              setType(val ?? "");
              setPage(1);
            }}
            data={[
              { value: "", label: t("transactions.all_types") },
              { value: "income", label: t("categories.type_income") },
              { value: "expense", label: t("categories.type_expense") },
            ]}
          />
        </SimpleGrid>

        <Switch
          label={t("transactions.filter_installments_only")}
          checked={showInstallmentsOnly}
          onChange={(e) => {
            setShowInstallmentsOnly(e.currentTarget.checked);
            setPage(1);
          }}
        />
      </SectionCard>

      {error && (
        <Alert color="red" mb="md" radius="md">
          {error}
        </Alert>
      )}
      {success && (
        <Alert color="green" mb="md" radius="md">
          {success}
        </Alert>
      )}

      {transactions.length === 0 ? (
        <Text c="dimmed" ta="center" py="xl" fs="italic">
          {t("transactions.empty")}
        </Text>
      ) : (
        <>
          <Stack gap="sm" mb="md">
            {transactions.map((tx) => (
              <Paper key={tx.id} shadow="xs" radius="md" p="md" withBorder>
                <Group justify="space-between" align="center">
                  <Stack gap={2}>
                    <Text
                      fw={600}
                      style={{ display: "inline-flex", alignItems: "center" }}
                    >
                      <Box
                        component="span"
                        style={{
                          width: "1.5rem",
                          height: "1.5rem",
                          borderRadius: 999,
                          display: "inline-flex",
                          alignItems: "center",
                          justifyContent: "center",
                          background: "var(--mantine-color-indigo-0)",
                          color: "var(--mantine-color-indigo-6)",
                          marginRight: "0.5rem",
                          flexShrink: 0,
                        }}
                        aria-hidden="true"
                      >
                        <i
                          className={getCategoryIconClass(tx.category?.icon)}
                        />
                      </Box>
                      {tx.category?.parent_id
                        ? `${categories.find((c) => c.id === tx.category!.parent_id)?.name} > ${tx.category.name}`
                        : tx.category?.name}
                    </Text>
                    <Text size="xs" c="dimmed">
                      {formatTransactionDate(tx.transacted_at)}
                    </Text>
                    {tx.notes && (
                      <Text size="sm" c="dimmed">
                        {tx.notes}
                      </Text>
                    )}
                    {tx.installment_number != null &&
                      tx.installment_total != null && (
                        <Text size="xs" c="indigo" fw={500}>
                          {t("transactions.installment_badge", {
                            current: tx.installment_number,
                            total: tx.installment_total,
                          })}
                        </Text>
                      )}
                  </Stack>

                  <Group gap="xs" align="center">
                    <Text
                      fw={700}
                      size="lg"
                      c={tx.type === "income" ? "green" : "red"}
                    >
                      {tx.type === "income" ? "+" : "-"} R${" "}
                      {Number(tx.amount).toFixed(2)}
                    </Text>
                    <ActionIcon
                      variant="subtle"
                      radius="xl"
                      size="sm"
                      onClick={() => handleStartEdit(tx)}
                      aria-label={t("transactions.edit")}
                      title={t("transactions.edit")}
                    >
                      <i
                        className="fa-solid fa-pen-to-square"
                        aria-hidden="true"
                        style={{ fontSize: "0.8rem" }}
                      />
                    </ActionIcon>
                    <ActionIcon
                      variant="subtle"
                      color="red"
                      radius="xl"
                      size="sm"
                      onClick={() =>
                        handleDelete(
                          tx.id,
                          tx.installment_group_id,
                          tx.installment_number != null
                            ? tx.installment_total
                            : null,
                        )
                      }
                      aria-label={t("transactions.delete")}
                      title={t("transactions.delete")}
                    >
                      <i
                        className="fa-solid fa-trash-can"
                        aria-hidden="true"
                        style={{ fontSize: "0.8rem" }}
                      />
                    </ActionIcon>
                  </Group>
                </Group>
              </Paper>
            ))}
          </Stack>

          {meta && meta.last_page > 1 && (
            <Group justify="center" gap="md" p="md">
              <Button
                variant="filled"
                size="sm"
                disabled={page === 1}
                onClick={() => setPage(page - 1)}
                leftSection={<span>←</span>}
              >
                {t("common.previous")}
              </Button>
              <Text c="dimmed" fw={500}>
                {page} / {meta.last_page}
              </Text>
              <Button
                variant="filled"
                size="sm"
                disabled={page === meta.last_page}
                onClick={() => setPage(page + 1)}
                rightSection={<span>→</span>}
              >
                {t("common.next")}
              </Button>
            </Group>
          )}
        </>
      )}

      <FormModal
        opened={formModal !== null}
        onClose={handleCloseModal}
        title={
          formModal?.mode === "create"
            ? t("transactions.new_entry")
            : t("transactions.modal_edit_title")
        }
      >
        <Box component="form" onSubmit={handleFormSubmit}>
          <SimpleGrid cols={{ base: 1, sm: 2 }} mb="sm">
            <Select
              label={t("transactions.type")}
              value={formType}
              onChange={(val) => {
                const nextType = val === "income" ? "income" : "expense";
                setFormType(nextType);
                setFormParentCategoryId("");
                setFormSubcategoryId("");
                if (nextType === "income") setInstallmentEnabled(false);
              }}
              data={[
                { value: "expense", label: t("categories.type_expense") },
                { value: "income", label: t("categories.type_income") },
              ]}
            />

            <Select
              label={t("transactions.parent_category")}
              value={formParentCategoryId}
              onChange={(val) => {
                setFormParentCategoryId(val ?? "");
                setFormSubcategoryId("");
              }}
              data={parentFormCategories.map((cat) => ({
                value: String(cat.id),
                label: cat.name,
              }))}
              placeholder={t("transactions.select_category")}
              required
            />

            {subcategoryOptions.length > 0 && (
              <Select
                label={t("transactions.subcategory")}
                value={formSubcategoryId}
                onChange={(val) => setFormSubcategoryId(val ?? "")}
                data={subcategoryOptions.map((cat) => ({
                  value: String(cat.id),
                  label: cat.name,
                }))}
                placeholder={t("transactions.select_subcategory")}
                clearable
              />
            )}

            {selectedFormCategory && (
              <Text
                size="sm"
                style={{ display: "inline-flex", alignItems: "center" }}
                c="dimmed"
              >
                <Box
                  component="span"
                  style={{
                    width: "1.5rem",
                    height: "1.5rem",
                    borderRadius: 999,
                    display: "inline-flex",
                    alignItems: "center",
                    justifyContent: "center",
                    background: "var(--mantine-color-indigo-0)",
                    color: "var(--mantine-color-indigo-6)",
                    marginRight: "0.5rem",
                    flexShrink: 0,
                  }}
                  aria-hidden="true"
                >
                  <i
                    className={getCategoryIconClass(selectedFormCategory.icon)}
                  />
                </Box>
                {selectedFormCategory.name}
              </Text>
            )}

            <Select
              label={t("transactions.payment_method")}
              value={formPaymentMethod}
              onChange={(val) => {
                const next = val ?? "pix";
                setFormPaymentMethod(next);
                if (next !== "credit_card") setInstallmentEnabled(false);
              }}
              data={paymentMethods.map((method) => ({
                value: method,
                label: t(`transactions.payment.${method}`),
              }))}
            />

            <NumberInput
              label={t("transactions.amount")}
              value={formAmount}
              onChange={setFormAmount}
              min={0.01}
              step={0.01}
              decimalScale={2}
              required
            />

            <TextInput
              label={t("transactions.date")}
              type="date"
              value={formTransactedAt}
              onChange={(e) => setFormTransactedAt(e.target.value)}
              required
            />

            <TextInput
              label={t("transactions.notes")}
              value={formNotes}
              onChange={(e) => setFormNotes(e.target.value)}
              style={{ gridColumn: "1 / -1" }}
            />
          </SimpleGrid>

          {formModal?.mode === "create" &&
            formPaymentMethod === "credit_card" &&
            formType === "expense" && (
              <Box mb="sm">
                <Switch
                  label={t("transactions.installment_toggle")}
                  checked={installmentEnabled}
                  onChange={(e) =>
                    setInstallmentEnabled(e.currentTarget.checked)
                  }
                  mb="sm"
                />
                {installmentEnabled && (
                  <SimpleGrid cols={{ base: 1, sm: 2 }}>
                    <NumberInput
                      label={t("transactions.installment_current")}
                      value={installmentCurrent}
                      onChange={setInstallmentCurrent}
                      min={1}
                      max={60}
                      required
                    />
                    <NumberInput
                      label={t("transactions.installment_total")}
                      value={installmentTotal}
                      onChange={setInstallmentTotal}
                      min={1}
                      max={60}
                      required
                    />
                  </SimpleGrid>
                )}
              </Box>
            )}

          {error && (
            <Alert color="red" mb="sm" radius="md">
              {error}
            </Alert>
          )}

          <ActionBar>
            <Button type="submit" loading={isSubmitting}>
              {formModal?.mode === "create"
                ? t("common.create")
                : t("common.save")}
            </Button>
            <Button type="button" variant="light" onClick={handleCloseModal}>
              {t("common.cancel")}
            </Button>
          </ActionBar>
        </Box>
      </FormModal>

      <ConfirmDialog
        opened={confirmState !== null}
        title={confirmState?.title ?? ""}
        message={confirmState?.message ?? ""}
        onConfirm={confirmState?.onConfirm ?? (() => {})}
        onCancel={() => setConfirmState(null)}
        loading={deleteTransaction.isPending}
      />
    </PageContainer>
  );
}
